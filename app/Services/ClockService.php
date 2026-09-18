<?php

namespace App\Services;

use App\Enums\ClockChannel;
use App\Enums\GeofenceMode;
use App\Enums\PunchType;
use App\Models\Location;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClockService
{
    public function __construct(
        private readonly TimeEntryRebuilder $rebuilder,
        private readonly PeriodLockService $locks,
    ) {}

    /**
     * @param  array{
     *     type?: string|null,
     *     channel?: string|null,
     *     occurred_at?: string|null,
     *     location_id?: int|null,
     *     latitude?: float|null,
     *     longitude?: float|null,
     *     gps_accuracy?: int|null,
     *     offline?: bool,
     *     client_event_id?: string|null,
     *     device_id?: string|null,
     *     reason?: string|null
     * }  $payload
     * @return array{punch: Punch, entry: TimeEntry, geofence: string}
     */
    public function punch(Person $person, ?User $actor, array $payload): array
    {
        if (! $person->isClockEligible()) {
            throw ValidationException::withMessages([
                'person' => 'Ova osoba nije u statusu koji se prijavljuje na posao.',
            ]);
        }

        $type = $this->resolveType($person, $payload['type'] ?? null);
        $channel = ClockChannel::tryFrom((string) ($payload['channel'] ?? ClockChannel::Web->value))
            ?? ClockChannel::Web;

        if ($channel === ClockChannel::Manager && blank($payload['reason'] ?? null)) {
            throw ValidationException::withMessages([
                'reason' => 'Ručni unos zahtijeva razlog.',
            ]);
        }

        $occurredAt = isset($payload['occurred_at'])
            ? Carbon::parse($payload['occurred_at'])->timezone(config('app.timezone'))
            : now();

        $location = $this->resolveLocation($person, $payload['location_id'] ?? null);
        $geofence = $this->evaluateGeofence(
            $location,
            isset($payload['latitude']) ? (float) $payload['latitude'] : null,
            isset($payload['longitude']) ? (float) $payload['longitude'] : null,
        );

        if ($geofence === 'fail' && $location?->geofence_mode === GeofenceMode::Strict) {
            throw ValidationException::withMessages([
                'geofence' => 'Prijava je izvan zone lokacije.',
            ]);
        }

        $clientEventId = $payload['client_event_id'] ?? (string) Str::uuid();

        $existing = Punch::query()
            ->where('organization_id', $person->organization_id)
            ->where('client_event_id', $clientEventId)
            ->first();

        if ($existing !== null) {
            return [
                'punch' => $existing,
                'entry' => $this->rebuilder->rebuild($person, $existing->occurred_at_device),
                'geofence' => $existing->geofence_result,
            ];
        }

        $this->locks->assertWritable($person, $occurredAt);
        $this->assertSequence($person, $type);

        $hashSource = implode('|', [
            $person->organization_id,
            $person->id,
            $type->value,
            $occurredAt->toIso8601String(),
            $clientEventId,
        ]);

        $punch = Punch::query()->create([
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'location_id' => $location?->id,
            'created_by_user_id' => $actor?->id,
            'type' => $type,
            'channel' => $channel,
            'occurred_at_device' => $occurredAt,
            'occurred_at_server' => now(),
            'device_id' => $payload['device_id'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'gps_accuracy' => $payload['gps_accuracy'] ?? null,
            'geofence_result' => $geofence,
            'offline' => (bool) ($payload['offline'] ?? false),
            'client_event_id' => $clientEventId,
            'reason' => $payload['reason'] ?? null,
            'raw_payload' => $payload,
            'integrity_hash' => hash('sha256', $hashSource),
        ]);

        $entry = $this->rebuilder->rebuild($person, $occurredAt);

        return [
            'punch' => $punch,
            'entry' => $entry,
            'geofence' => $geofence,
        ];
    }

    public function correct(Person $person, User $actor, Punch $original, Carbon $occurredAt, string $reason): Punch
    {
        if ((int) $original->person_id !== (int) $person->id) {
            throw ValidationException::withMessages([
                'punch_id' => 'Prijava ne pripada ovoj osobi.',
            ]);
        }

        if ($original->corrections()->exists()) {
            throw ValidationException::withMessages([
                'punch_id' => 'Ta prijava je već ispravljena. Odaberite zadnji slog.',
            ]);
        }

        $this->locks->assertWritable($person, $original->occurred_at_device);
        $this->locks->assertWritable($person, $occurredAt);

        $clientEventId = (string) Str::uuid();
        $hashSource = implode('|', [
            $person->organization_id,
            $person->id,
            $original->type->value,
            $occurredAt->toIso8601String(),
            $clientEventId,
            'correction:'.$original->id,
        ]);

        $punch = Punch::query()->create([
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'location_id' => $original->location_id,
            'created_by_user_id' => $actor->id,
            'correction_of_id' => $original->id,
            'type' => $original->type,
            'channel' => ClockChannel::Workflow,
            'occurred_at_device' => $occurredAt,
            'occurred_at_server' => now(),
            'geofence_result' => 'skipped',
            'offline' => false,
            'client_event_id' => $clientEventId,
            'reason' => $reason,
            'raw_payload' => [
                'correction_of_id' => $original->id,
                'original_occurred_at' => $original->occurred_at_device->toIso8601String(),
            ],
            'integrity_hash' => hash('sha256', $hashSource),
        ]);

        $this->rebuilder->rebuild($person, $original->occurred_at_device);
        if ($original->occurred_at_device->toDateString() !== $occurredAt->toDateString()) {
            $this->rebuilder->rebuild($person, $occurredAt);
        }

        return $punch;
    }

    public function currentState(Person $person): ?PunchType
    {
        $last = Punch::query()
            ->where('person_id', $person->id)
            ->whereDoesntHave('corrections')
            ->orderByDesc('occurred_at_device')
            ->orderByDesc('id')
            ->first();

        return $last?->type;
    }

    public function nextSuggestedType(Person $person): PunchType
    {
        return match ($this->currentState($person)) {
            PunchType::In, PunchType::BreakEnd => PunchType::Out,
            PunchType::BreakStart => PunchType::BreakEnd,
            default => PunchType::In,
        };
    }

    public function isClockedIn(Person $person): bool
    {
        return in_array($this->currentState($person), [
            PunchType::In,
            PunchType::BreakStart,
            PunchType::BreakEnd,
        ], true);
    }

    private function resolveType(Person $person, ?string $requested): PunchType
    {
        if ($requested === null || $requested === '') {
            return $this->nextSuggestedType($person);
        }

        return PunchType::tryFrom($requested)
            ?? throw ValidationException::withMessages(['type' => 'Nepoznata vrsta prijave.']);
    }

    private function assertSequence(Person $person, PunchType $type): void
    {
        $current = $this->currentState($person);

        $illegal = match ($type) {
            PunchType::In => in_array($current, [PunchType::In, PunchType::BreakStart, PunchType::BreakEnd], true),
            PunchType::Out => $current === null || $current === PunchType::Out,
            PunchType::BreakStart => $current !== PunchType::In && $current !== PunchType::BreakEnd,
            PunchType::BreakEnd => $current !== PunchType::BreakStart,
        };

        if ($illegal) {
            throw ValidationException::withMessages([
                'type' => 'Ova prijava/odjava ne odgovara trenutnom stanju osobe.',
            ]);
        }
    }

    private function resolveLocation(Person $person, mixed $locationId): ?Location
    {
        if ($locationId) {
            return Location::query()
                ->forOrganization($person->organization)
                ->where('id', $locationId)
                ->first();
        }

        return $person->location;
    }

    private function evaluateGeofence(?Location $location, ?float $lat, ?float $lng): string
    {
        if ($location === null || $location->geofence_mode === GeofenceMode::Off || ! $location->hasCoordinates()) {
            return 'skipped';
        }

        if ($lat === null || $lng === null) {
            return $location->geofence_mode === GeofenceMode::Strict ? 'fail' : 'skipped';
        }

        $distance = $this->distanceMeters(
            (float) $location->latitude,
            (float) $location->longitude,
            $lat,
            $lng,
        );

        return $distance <= (int) $location->radius_meters ? 'pass' : 'fail';
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ClockChannel;
use App\Models\Person;
use App\Services\ClockQrService;
use App\Services\ClockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalClockController extends Controller
{
    public function __construct(
        private readonly ClockService $clock,
        private readonly ClockQrService $qr,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $organization = app('currentOrganization');
        $location = app('currentTerminalLocation');
        $data = $request->validate([
            'pin' => ['nullable', 'string', 'max:6'],
            'qr' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:20'],
            'client_event_id' => ['nullable', 'string', 'max:64'],
        ]);

        $person = $this->identify($organization->id, $data['pin'] ?? null, $data['qr'] ?? null);
        abort_if($person === null || ! $person->isClockEligible(), 422, 'Osoba nije prepoznata.');

        $result = $this->clock->punch($person, null, [
            'type' => $data['type'] ?? null,
            'channel' => ClockChannel::Terminal->value,
            'location_id' => $location->id,
            'client_event_id' => $data['client_event_id'] ?? null,
            'client_ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => $result['punch']->type->label().' zabilježena.',
            'person' => $person->fullName(),
            'punch' => [
                'id' => $result['punch']->id,
                'type' => $result['punch']->type->value,
                'occurred_at' => $result['punch']->occurred_at_device->toIso8601String(),
            ],
            'clocked_in' => $this->clock->isClockedIn($person),
            'next_type' => $this->clock->nextSuggestedType($person)->value,
            'exception' => $result['entry']->exception_code,
        ]);
    }

    private function identify(int $organizationId, ?string $pin, ?string $qr): ?Person
    {
        $hasPin = filled($pin);
        $hasQr = filled($qr);
        if ($hasPin === $hasQr) {
            return null;
        }

        if ($hasPin) {
            return Person::query()
                ->where('organization_id', $organizationId)
                ->where('clock_pin', $pin)
                ->first();
        }

        $slug = app('currentOrganization')->slug;
        $token = $this->qr->parse((string) $qr, $slug);
        if ($token === null) {
            return null;
        }

        return Person::query()
            ->where('organization_id', $organizationId)
            ->where('clock_qr', $token)
            ->first();
    }
}

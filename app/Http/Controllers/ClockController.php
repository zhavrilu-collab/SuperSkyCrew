<?php

namespace App\Http\Controllers;

use App\Enums\ClockChannel;
use App\Enums\PunchType;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Services\ClockService;
use App\Services\FeatureService;
use App\Services\OrganizationRbacService;
use App\Support\OrganizationFeatures;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClockController extends Controller
{
    public function __construct(
        private readonly ClockService $clock,
        private readonly OrganizationRbacService $rbac,
        private readonly FeatureService $features,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->features->assertEnabled($organization, OrganizationFeatures::CLOCK_MOBILE);
        $person = $this->resolvePerson($request, forOthers: false);

        if ($person === null) {
            return redirect()
                ->route('organization.dashboard', $organization->slug)
                ->withErrors(['clock' => 'Nemate povezanu karticu radnika za prijavu.']);
        }

        $todayPunches = Punch::query()
            ->where('person_id', $person->id)
            ->whereDate('occurred_at_device', now()->toDateString())
            ->with('corrections')
            ->orderBy('occurred_at_device')
            ->get();

        $person->loadMissing('location');
        $tz = config('app.timezone');
        $weekStart = now()->timezone($tz)->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $weekMinutes = (int) TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', '>=', $weekStart->toDateString())
            ->whereDate('work_date', '<=', $weekEnd->toDateString())
            ->sum('total_minutes');
        $openExceptions = TimeEntry::query()
            ->where('person_id', $person->id)
            ->openExceptions()
            ->orderByDesc('work_date')
            ->limit(5)
            ->get();

        return view('organization.clock', [
            'organization' => $organization,
            'person' => $person,
            'clockedIn' => $this->clock->isClockedIn($person),
            'nextType' => $this->clock->nextSuggestedType($person),
            'currentState' => $this->clock->currentState($person),
            'todayPunches' => $todayPunches,
            'requirePhoto' => (bool) $person->location?->require_photo,
            'allowOffline' => $person->location?->allow_offline ?? true,
            'deviceBind' => $person->location?->device_bind_mode?->value ?? 'off',
            'weekMinutes' => $weekMinutes,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'openExceptions' => $openExceptions,
            'pushEnabled' => $organization->feature(OrganizationFeatures::PUSH),
        ]);
    }

    public function manifest(): JsonResponse
    {
        $organization = app('currentOrganization');
        $palette = $organization->themePalette();

        return response()->json([
            'name' => $organization->name.' — prijava',
            'short_name' => 'Prijava',
            'start_url' => route('organization.clock', $organization->slug, false),
            'scope' => '/'.$organization->slug.'/',
            'display' => 'standalone',
            'background_color' => '#112b12',
            'theme_color' => $palette['primary'] ?? '#1b431c',
            'lang' => 'hr',
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $organization = app('currentOrganization');
        $person = $this->resolveClockPerson($request);
        abort_if($person === null, 403, 'Nemate povezanu karticu radnika za prijavu.');

        $channel = $request->input('channel', ClockChannel::Web->value);
        if ($request->boolean('from_pwa')) {
            $channel = ClockChannel::Pwa->value;
        }
        if ($request->attributes->has('clockPerson') && ! $request->user()) {
            $channel = $request->input('channel', ClockChannel::Api->value);
        }

        $result = $this->clock->punch($person, $request->user(), [
            'type' => $request->input('type'),
            'channel' => $channel,
            'occurred_at' => $request->input('occurred_at'),
            'location_id' => $request->input('location_id'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'gps_accuracy' => $request->input('gps_accuracy'),
            'offline' => $request->boolean('offline'),
            'client_event_id' => $request->input('client_event_id'),
            'device_id' => $request->input('device_id'),
            'client_ip' => $request->ip(),
            'reason' => $request->input('reason'),
            'photo' => $request->file('photo'),
            'photo_data' => $request->input('photo_data'),
            'mock_gps' => $request->boolean('mock_gps'),
        ]);

        $message = $result['punch']->type->label().' zabilježena u '
            .$result['punch']->occurred_at_device->timezone(config('app.timezone'))->format('H:i');

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'punch' => [
                    'id' => $result['punch']->id,
                    'type' => $result['punch']->type->value,
                    'occurred_at' => $result['punch']->occurred_at_device->toIso8601String(),
                    'offline' => $result['punch']->offline,
                ],
                'clocked_in' => $this->clock->isClockedIn($person),
                'next_type' => $this->clock->nextSuggestedType($person)->value,
                'state' => $this->clock->currentState($person)?->value,
                'geofence' => $result['geofence'],
                'exception' => $result['entry']->exception_code,
                'entry_status' => $result['entry']->status->value,
                'total_minutes' => $result['entry']->total_minutes,
            ]);
        }

        return redirect()
            ->route('organization.clock', $organization->slug)
            ->with('status', $message);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $response = $this->store($request);
        if ($response instanceof RedirectResponse) {
            return response()->json(['message' => $response->getSession()->get('status') ?: 'Prijava zabilježena.']);
        }

        return $response;
    }

    private function resolveClockPerson(Request $request): ?Person
    {
        $fromToken = $request->attributes->get('clockPerson');
        if ($fromToken instanceof Person && ! $request->filled('person_id')) {
            return $fromToken;
        }

        return $this->resolvePerson($request, forOthers: $request->user() !== null);
    }

    private function resolvePerson(Request $request, bool $forOthers): ?Person
    {
        $organization = app('currentOrganization');
        $userId = (int) Auth::id();

        $own = Person::query()
            ->forOrganization($organization)
            ->where('user_id', $userId)
            ->first();

        $requestedId = $request->input('person_id');
        if (! $requestedId) {
            return $own;
        }

        $canPunchOthers = $forOthers && (
            $this->rbac->can($organization->id, $userId, 'time.access')
            || $this->rbac->can($organization->id, $userId, 'people.access')
        );

        if (! $canPunchOthers) {
            return $own;
        }

        return Person::query()
            ->forOrganization($organization)
            ->where('id', $requestedId)
            ->first();
    }
}

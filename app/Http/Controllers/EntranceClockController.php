<?php

namespace App\Http\Controllers;

use App\Enums\ClockChannel;
use App\Models\Location;
use App\Models\Person;
use App\Services\ClockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EntranceClockController extends Controller
{
    public function __construct(private readonly ClockService $clock) {}

    public function show(Request $request, string $slug, string $token): View|RedirectResponse
    {
        $organization = app('currentOrganization');
        $location = $this->location($organization->id, $token);
        $person = $this->ownPerson($organization->id);

        if ($person === null) {
            return redirect()
                ->route('organization.dashboard', $organization->slug)
                ->withErrors(['clock' => 'Nemate povezanu karticu radnika za ulaznu prijavu.']);
        }

        return view('organization.entrance', [
            'organization' => $organization,
            'location' => $location,
            'person' => $person,
            'clockedIn' => $this->clock->isClockedIn($person),
            'nextType' => $this->clock->nextSuggestedType($person),
        ]);
    }

    public function store(Request $request, string $slug, string $token): RedirectResponse
    {
        $organization = app('currentOrganization');
        $location = $this->location($organization->id, $token);
        $person = $this->ownPerson($organization->id);
        abort_if($person === null, 403, 'Nemate povezanu karticu radnika za ulaznu prijavu.');

        $result = $this->clock->punch($person, $request->user(), [
            'type' => $request->input('type'),
            'channel' => ClockChannel::Entrance->value,
            'location_id' => $location->id,
            'client_event_id' => $request->input('client_event_id'),
            'client_ip' => $request->ip(),
        ]);

        $message = $result['punch']->type->label().' na '.$location->name.' · '
            .$result['punch']->occurred_at_device->timezone(config('app.timezone'))->format('H:i');

        return redirect()
            ->route('organization.entrance', [$organization->slug, $token])
            ->with('status', $message);
    }

    private function location(int $organizationId, string $token): Location
    {
        $location = Location::query()
            ->where('organization_id', $organizationId)
            ->where('entrance_token', $token)
            ->where('is_active', true)
            ->first();
        abort_if($location === null, 404);

        return $location;
    }

    private function ownPerson(int $organizationId): ?Person
    {
        return Person::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', Auth::id())
            ->first();
    }
}

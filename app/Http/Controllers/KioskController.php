<?php

namespace App\Http\Controllers;

use App\Enums\ClockChannel;
use App\Models\Person;
use App\Services\ClockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KioskController extends Controller
{
    public function __construct(
        private readonly ClockService $clock,
    ) {}

    public function show(Request $request): View
    {
        $organization = app('currentOrganization');
        $location = app('currentKioskLocation');
        $person = $this->identifiedPerson($request);

        return view('kiosk.show', [
            'organization' => $organization,
            'location' => $location,
            'person' => $person,
            'clockedIn' => $person ? $this->clock->isClockedIn($person) : false,
            'nextType' => $person ? $this->clock->nextSuggestedType($person) : null,
            'currentState' => $person ? $this->clock->currentState($person) : null,
        ]);
    }

    public function identify(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $location = app('currentKioskLocation');
        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $person = Person::query()
            ->forOrganization($organization)
            ->where('clock_pin', $data['pin'])
            ->first();

        if ($person === null || ! $person->isClockEligible()) {
            return back()->withErrors(['pin' => 'PIN nije prepoznat.']);
        }

        $request->session()->put($this->sessionKey($location->kiosk_token), $person->id);

        return redirect()->route('organization.kiosk', [
            'slug' => $organization->slug,
            'token' => $location->kiosk_token,
        ]);
    }

    public function punch(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $location = app('currentKioskLocation');
        $person = $this->identifiedPerson($request);

        if ($person === null) {
            return redirect()
                ->route('organization.kiosk', ['slug' => $organization->slug, 'token' => $location->kiosk_token])
                ->withErrors(['pin' => 'Najprije unesite PIN.']);
        }

        $result = $this->clock->punch($person, null, [
            'type' => $request->input('type'),
            'channel' => ClockChannel::Kiosk->value,
            'location_id' => $location->id,
            'client_event_id' => $request->input('client_event_id'),
            'device_id' => $request->cookie('kiosk_device') ?: $request->session()->getId(),
        ]);

        $request->session()->forget($this->sessionKey($location->kiosk_token));

        $message = $person->fullName().' · '.$result['punch']->type->label().' u '
            .$result['punch']->occurred_at_device->timezone(config('app.timezone'))->format('H:i');

        return redirect()
            ->route('organization.kiosk', ['slug' => $organization->slug, 'token' => $location->kiosk_token])
            ->with('status', $message);
    }

    public function reset(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $location = app('currentKioskLocation');
        $request->session()->forget($this->sessionKey($location->kiosk_token));

        return redirect()->route('organization.kiosk', [
            'slug' => $organization->slug,
            'token' => $location->kiosk_token,
        ]);
    }

    private function identifiedPerson(Request $request): ?Person
    {
        $organization = app('currentOrganization');
        $location = app('currentKioskLocation');
        $personId = $request->session()->get($this->sessionKey($location->kiosk_token));

        if (! $personId) {
            return null;
        }

        return Person::query()
            ->forOrganization($organization)
            ->where('id', $personId)
            ->first();
    }

    private function sessionKey(string $token): string
    {
        return 'kiosk.'.$token.'.person_id';
    }
}

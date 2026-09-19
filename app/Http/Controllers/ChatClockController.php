<?php

namespace App\Http\Controllers;

use App\Enums\ClockChannel;
use App\Models\Person;
use App\Services\ClockService;
use App\Services\FeatureService;
use App\Support\OrganizationFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatClockController extends Controller
{
    public function __construct(
        private readonly ClockService $clock,
        private readonly FeatureService $features,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $organization = app('currentOrganization');
        $this->features->assertEnabled($organization, OrganizationFeatures::CLOCK_CHAT);
        $person = $request->attributes->get('clockPerson');
        abort_unless($person instanceof Person, 401, 'Clock token nije valjan.');

        $text = mb_strtolower(trim((string) $request->input('text', $request->input('type', ''))));
        $type = match (true) {
            in_array($text, ['odjava', 'out', 'odjavi'], true) => 'out',
            in_array($text, ['pauza', 'break', 'break_start'], true) => 'break_start',
            in_array($text, ['kraj pauze', 'break_end'], true) => 'break_end',
            $text === 'in' || $text === 'prijava' || $text === '' => $request->input('type'),
            default => $request->input('type'),
        };

        $result = $this->clock->punch($person, null, [
            'type' => $type,
            'channel' => ClockChannel::Chat->value,
            'location_id' => $request->input('location_id') ?: $person->location_id,
            'client_event_id' => $request->input('client_event_id'),
            'client_ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => $result['punch']->type->label().' zabilježena.',
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
}

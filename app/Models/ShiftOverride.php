<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftOverride extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'shift_id',
        'work_date',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(WorkflowRequest::class, 'request_id');
    }

    public static function putForDay(
        int $organizationId,
        int $personId,
        string $workDate,
        ?int $shiftId,
        ?int $requestId = null,
    ): self {
        $row = static::query()
            ->where('person_id', $personId)
            ->whereDate('work_date', $workDate)
            ->first();

        $payload = [
            'organization_id' => $organizationId,
            'person_id' => $personId,
            'work_date' => $workDate,
            'shift_id' => $shiftId,
            'request_id' => $requestId,
        ];

        if ($row !== null) {
            $row->fill($payload)->save();

            return $row;
        }

        return static::query()->create($payload);
    }
}

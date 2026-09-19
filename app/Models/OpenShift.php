<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenShift extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'shift_id',
        'department_id',
        'work_date',
        'slots',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'slots' => 'integer',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function claimedCount(): int
    {
        return ShiftOverride::query()
            ->where('organization_id', $this->organization_id)
            ->whereDate('work_date', $this->work_date->toDateString())
            ->where('shift_id', $this->shift_id)
            ->count();
    }

    public function remainingSlots(): int
    {
        return max(0, (int) $this->slots - $this->claimedCount());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'year',
        'entitled_days',
        'carried_days',
        'used_days',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function remainingOld(): int
    {
        return max(0, $this->carried_days - min($this->used_days, $this->carried_days));
    }

    public function remainingNew(): int
    {
        return max(0, $this->entitled_days - max(0, $this->used_days - $this->carried_days));
    }

    public function remaining(): int
    {
        return $this->remainingOld() + $this->remainingNew();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodLock extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'year',
        'month',
        'locked_by_user_id',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
        ];
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function label(): string
    {
        return sprintf('%02d/%d', $this->month, $this->year);
    }
}

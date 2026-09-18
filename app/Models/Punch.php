<?php

namespace App\Models;

use App\Enums\ClockChannel;
use App\Enums\PunchType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Punch extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'location_id',
        'created_by_user_id',
        'correction_of_id',
        'type',
        'channel',
        'occurred_at_device',
        'occurred_at_server',
        'device_id',
        'latitude',
        'longitude',
        'gps_accuracy',
        'geofence_result',
        'offline',
        'client_event_id',
        'reason',
        'raw_payload',
        'integrity_hash',
    ];

    protected function casts(): array
    {
        return [
            'type' => PunchType::class,
            'channel' => ClockChannel::class,
            'occurred_at_device' => 'datetime',
            'occurred_at_server' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'offline' => 'boolean',
            'raw_payload' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'correction_of_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'correction_of_id');
    }

    public function isCorrection(): bool
    {
        return $this->correction_of_id !== null;
    }
}

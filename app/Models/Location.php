<?php

namespace App\Models;

use App\Enums\DeviceBindMode;
use App\Enums\GeofenceMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends OrganizationModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'latitude',
        'longitude',
        'radius_meters',
        'geofence_mode',
        'is_active',
        'kiosk_enabled',
        'kiosk_token',
        'require_photo',
        'allow_offline',
        'device_bind_mode',
        'allowed_channels',
        'punch_grace_minutes',
        'punch_round_minutes',
        'entrance_token',
        'terminal_token',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
            'geofence_mode' => GeofenceMode::class,
            'kiosk_enabled' => 'boolean',
            'require_photo' => 'boolean',
            'allow_offline' => 'boolean',
            'device_bind_mode' => DeviceBindMode::class,
            'is_active' => 'boolean',
            'allowed_channels' => 'array',
            'punch_grace_minutes' => 'integer',
            'punch_round_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Location $location) {
            if (blank($location->entrance_token)) {
                $location->entrance_token = \Illuminate\Support\Str::random(32);
            }
            if (blank($location->terminal_token)) {
                $location->terminal_token = \Illuminate\Support\Str::random(32);
            }
        });
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null && $this->radius_meters;
    }

    public function kioskUrl(): ?string
    {
        if (! $this->kiosk_enabled || blank($this->kiosk_token) || $this->organization?->slug === null) {
            return null;
        }

        return route('organization.kiosk', [
            'slug' => $this->organization->slug,
            'token' => $this->kiosk_token,
        ]);
    }

    public function entranceUrl(): ?string
    {
        $slug = $this->organization?->slug;
        if (blank($this->entrance_token) || $slug === null) {
            return null;
        }

        return route('organization.entrance', [
            'slug' => $slug,
            'token' => $this->entrance_token,
        ]);
    }

    public function allowsChannel(\App\Enums\ClockChannel $channel): bool
    {
        $allowed = $this->allowed_channels;
        if (! is_array($allowed) || $allowed === []) {
            return true;
        }

        return in_array($channel->value, $allowed, true);
    }
}

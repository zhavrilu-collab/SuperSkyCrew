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
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
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
}

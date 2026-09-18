<?php

namespace Database\Factories;

use App\Enums\GeofenceMode;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Sjedište',
            'latitude' => 45.8150,
            'longitude' => 15.9819,
            'radius_meters' => 200,
            'geofence_mode' => GeofenceMode::Off,
            'is_active' => true,
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'plan',
        'status_changed_at',
        'email',
        'oib',
        'phone',
        'city',
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OrganizationUser, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function jobPositions(): HasMany
    {
        return $this->hasMany(JobPosition::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function calendarRules(): HasMany
    {
        return $this->hasMany(CalendarRule::class);
    }
}

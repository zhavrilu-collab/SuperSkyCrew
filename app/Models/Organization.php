<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Services\FeatureService;
use App\Support\OrganizationThemes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'plan',
        'employee_limit',
        'features',
        'status_changed_at',
        'email',
        'oib',
        'mbs',
        'phone',
        'street',
        'city',
        'website',
        'nkd',
        'stripe_customer_id',
        'stripe_subscription_id',
        'trial_ends_at',
        'organization_type',
        'theme_key',
        'logo_path',
        'volunteer_module',
        'expiry_warning_days',
        'annual_leave_base_days',
        'annual_leave_days_per_child',
        'period_lock_day',
        'show_clock_bounds',
    ];

    protected $attributes = [
        'theme_key' => 'tirkizna',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
            'status_changed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'organization_type' => OrganizationType::class,
            'volunteer_module' => 'boolean',
            'expiry_warning_days' => 'integer',
            'annual_leave_base_days' => 'integer',
            'annual_leave_days_per_child' => 'integer',
            'period_lock_day' => 'integer',
            'show_clock_bounds' => 'boolean',
            'employee_limit' => 'integer',
            'features' => 'array',
        ];
    }

    public function feature(string $key): bool
    {
        return app(FeatureService::class)->enabled($this, $key);
    }

    public function navbarBrandPrefix(): string
    {
        return mb_strtoupper($this->name);
    }

    public function logoUrl(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function isNonprofit(): bool
    {
        return $this->organization_type === OrganizationType::Nonprofit;
    }

    public function isCraft(): bool
    {
        return $this->organization_type === OrganizationType::Craft;
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture()
            && ! filled($this->stripe_subscription_id);
    }

    public function trialExpired(): bool
    {
        return $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast()
            && ! filled($this->stripe_subscription_id);
    }

    public function trialDaysRemaining(): int
    {
        if (! $this->onTrial()) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->startOfDay(), false));
    }

    public function themePalette(): array
    {
        return OrganizationThemes::paletteFor($this);
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

    public function legalEntities(): HasMany
    {
        return $this->hasMany(LegalEntity::class);
    }

    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class);
    }

    public function enterpriseUnits(): HasMany
    {
        return $this->hasMany(EnterpriseUnit::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function calendarRules(): HasMany
    {
        return $this->hasMany(CalendarRule::class);
    }

    public function leaveTenureRules(): HasMany
    {
        return $this->hasMany(LeaveTenureRule::class)->orderBy('min_years');
    }
}

<?php

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\FamilyRight;
use App\Enums\OtherFoKind;
use App\Enums\PersonStatus;
use App\Services\ClockQrService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Person extends OrganizationModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'manager_user_id',
        'location_id',
        'department_id',
        'job_position_id',
        'cost_center_id',
        'legal_entity_id',
        'work_center_id',
        'first_name',
        'last_name',
        'oib',
        'gender',
        'date_of_birth',
        'citizenship',
        'residence',
        'job_title',
        'contract_type',
        'status',
        'started_at',
        'ended_at',
        'ended_reason',
        'insurance_filed_at',
        'work_permit_expires_at',
        'medical_expires_at',
        'certificate_expires_at',
        'annual_leave_days',
        'annual_leave_manual',
        'iban',
        'pay_coefficient',
        'allowance_percent',
        'prior_service_months',
        'children_count',
        'dependents_count',
        'tax_relief_note',
        'family_right',
        'znr_exam_required',
        'fo_kind',
        'instrument_title',
        'host_employer',
        'assignment_clocks',
        'executive_autonomy',
        'clock_pin',
        'clock_qr',
        'clock_api_token',
        'clock_device_id',
        'cv_path',
        'cv_original_name',
        'cv_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PersonStatus::class,
            'contract_type' => ContractType::class,
            'date_of_birth' => 'date',
            'started_at' => 'date',
            'ended_at' => 'date',
            'insurance_filed_at' => 'date',
            'work_permit_expires_at' => 'date',
            'medical_expires_at' => 'date',
            'certificate_expires_at' => 'date',
            'annual_leave_manual' => 'boolean',
            'pay_coefficient' => 'decimal:4',
            'allowance_percent' => 'decimal:2',
            'prior_service_months' => 'integer',
            'children_count' => 'integer',
            'dependents_count' => 'integer',
            'family_right' => FamilyRight::class,
            'znr_exam_required' => 'boolean',
            'fo_kind' => OtherFoKind::class,
            'assignment_clocks' => 'boolean',
            'executive_autonomy' => 'boolean',
            'cv_uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Person $person) {
            if (! $person->organization_id) {
                return;
            }
            if (blank($person->clock_qr)) {
                $person->clock_qr = app(ClockQrService::class)->uniqueToken((int) $person->organization_id);
            }
            if (blank($person->clock_api_token)) {
                $person->clock_api_token = bin2hex(random_bytes(16));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function punches(): HasMany
    {
        return $this->hasMany(Punch::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function workflowRequests(): HasMany
    {
        return $this->hasMany(WorkflowRequest::class);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(DocumentHandover::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class)->orderBy('expires_at')->orderBy('title');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PersonDocument::class)->orderBy('expires_on')->orderBy('id');
    }

    public function interviewNotes(): HasMany
    {
        return $this->hasMany(InterviewNote::class)->orderByDesc('occurred_on')->orderByDesc('id');
    }

    public function hasCv(): bool
    {
        return filled($this->cv_path);
    }

    public function deleteCv(): void
    {
        if ($this->cv_path) {
            Storage::disk('local')->delete($this->cv_path);
        }

        $this->forceFill([
            'cv_path' => null,
            'cv_original_name' => null,
            'cv_uploaded_at' => null,
        ])->save();
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(PersonEngagement::class)->orderByDesc('valid_from')->orderByDesc('id');
    }

    public function assignmentOn(\Carbon\Carbon $on): ?PersonEngagement
    {
        return app(\App\Services\PersonEngagementService::class)->forPersonOn($this, $on);
    }

    public function employmentContracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class)->orderByDesc('starts_at')->orderByDesc('id');
    }

    public function currentContract(): ?EmploymentContract
    {
        return $this->employmentContracts->firstWhere('is_current', true)
            ?? $this->employmentContracts->first();
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1));
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'm' => 'M',
            'z' => 'Ž',
            'x' => 'Ostalo',
            default => '—',
        };
    }

    public function isClockEligible(): bool
    {
        if (! $this->status->clocksIn()) {
            return false;
        }

        if ($this->status === PersonStatus::Assigned) {
            return (bool) $this->assignment_clocks;
        }

        return true;
    }

    public function hasRelaxedTimeRecord(): bool
    {
        return $this->status === PersonStatus::Executive && $this->executive_autonomy;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeClockEligible(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereIn('status', [
                PersonStatus::Employee->value,
                PersonStatus::OtherFo->value,
                PersonStatus::Contractor->value,
                PersonStatus::Executive->value,
            ])->orWhere(function (Builder $assigned) {
                $assigned->where('status', PersonStatus::Assigned->value)
                    ->where('assignment_clocks', true);
            });
        });
    }

    public function jobLabel(): string
    {
        return $this->jobPosition?->name ?: ($this->job_title ?: '—');
    }

    public function engagementLabel(): string
    {
        $status = $this->status->label();
        if ($this->status === PersonStatus::OtherFo && $this->fo_kind) {
            return $status.' · '.$this->fo_kind->label();
        }
        if ($this->status === PersonStatus::Contractor && $this->instrument_title) {
            return $status.' · '.$this->instrument_title;
        }
        if ($this->status === PersonStatus::Assigned && $this->host_employer) {
            return $status.' · '.$this->host_employer;
        }
        if ($this->status === PersonStatus::Executive && $this->executive_autonomy) {
            return $status.' · samostalnost (čl. 21.)';
        }

        return $status;
    }

    public function instrumentLabel(): string
    {
        if ($this->instrument_title) {
            return $this->instrument_title;
        }
        if ($this->currentContract()) {
            return $this->currentContract()->summary();
        }

        return $this->contract_type?->label() ?: '—';
    }

    public function priorServiceLabel(): string
    {
        if ($this->prior_service_months === null) {
            return '—';
        }

        $years = intdiv((int) $this->prior_service_months, 12);
        $months = ((int) $this->prior_service_months) % 12;
        $parts = [];
        if ($years > 0) {
            $parts[] = $years.' g.';
        }
        if ($months > 0 || $years === 0) {
            $parts[] = $months.' mj.';
        }

        return implode(' ', $parts);
    }
}

<?php

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\FamilyRight;
use App\Enums\PersonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'iban',
        'pay_coefficient',
        'allowance_percent',
        'prior_service_months',
        'children_count',
        'dependents_count',
        'tax_relief_note',
        'family_right',
        'znr_exam_required',
        'clock_pin',
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
            'pay_coefficient' => 'decimal:4',
            'allowance_percent' => 'decimal:2',
            'prior_service_months' => 'integer',
            'children_count' => 'integer',
            'dependents_count' => 'integer',
            'family_right' => FamilyRight::class,
            'znr_exam_required' => 'boolean',
        ];
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
        return $this->status->clocksIn();
    }

    public function jobLabel(): string
    {
        return $this->jobPosition?->name ?: ($this->job_title ?: '—');
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

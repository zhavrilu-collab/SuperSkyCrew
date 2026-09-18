<?php

namespace App\Models;

use App\Enums\ContractType;
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
}

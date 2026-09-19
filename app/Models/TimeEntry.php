<?php

namespace App\Models;

use App\Enums\ExceptionCode;
use App\Enums\TimeEntryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'planned_shift_id',
        'planned_start',
        'planned_end',
        'work_date',
        'started_at',
        'ended_at',
        'break_minutes',
        'downtime_minutes',
        'total_minutes',
        'field_work_minutes',
        'standby_minutes',
        'night_minutes',
        'overtime_minutes',
        'approved_overtime_minutes',
        'sunday_minutes',
        'holiday_minutes',
        'split_shift_minutes',
        'shift_minutes',
        'evidential_minutes',
        'evidential_manual',
        'evidential_code',
        'evidential_note',
        'evidential_cost_center_id',
        'absence_code',
        'absence_minutes',
        'status',
        'exception_code',
        'exception_resolved_at',
        'exception_resolved_by',
        'exception_note',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'planned_start' => 'datetime',
            'planned_end' => 'datetime',
            'status' => TimeEntryStatus::class,
            'exception_resolved_at' => 'datetime',
            'evidential_manual' => 'boolean',
        ];
    }

    public function plannedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'planned_shift_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exception_resolved_by');
    }

    public function evidentialCostCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'evidential_cost_center_id');
    }

    public function totalHours(): float
    {
        return round($this->total_minutes / 60, 2);
    }

    public function isLocked(): bool
    {
        return $this->status === TimeEntryStatus::Locked;
    }

    public function exception(): ?ExceptionCode
    {
        return ExceptionCode::tryFrom((string) $this->exception_code);
    }

    public function exceptionLabel(): string
    {
        return ExceptionCode::tryLabel($this->exception_code);
    }

    public function hasOpenException(): bool
    {
        return $this->exception_code !== null && $this->exception_resolved_at === null;
    }

    public function scopeOpenExceptions(Builder $query): Builder
    {
        $today = now()->timezone(config('app.timezone'))->toDateString();

        return $query
            ->whereNotNull('exception_code')
            ->whereNull('exception_resolved_at')
            ->where(function (Builder $inner) use ($today) {
                $inner->where('exception_code', '!=', ExceptionCode::MissingOut->value)
                    ->orWhereDate('work_date', '<', $today);
            });
    }
}

<?php

namespace App\Models;

use App\Enums\PersonStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonEngagement extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'status',
        'department_id',
        'job_position_id',
        'location_id',
        'cost_center_id',
        'legal_entity_id',
        'work_center_id',
        'manager_user_id',
        'job_title',
        'valid_from',
        'valid_to',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PersonStatus::class,
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function jobLabel(): string
    {
        return $this->jobPosition?->name ?: ($this->job_title ?: '—');
    }

    public function periodLabel(): string
    {
        $from = $this->valid_from?->format('d.m.Y.') ?: '—';
        $to = $this->valid_to?->format('d.m.Y.') ?: 'danas';

        return $from.' – '.$to;
    }

    public function covers(Carbon $on): bool
    {
        $day = $on->toDateString();
        if ($this->valid_from && $this->valid_from->toDateString() > $day) {
            return false;
        }

        return $this->valid_to === null || $this->valid_to->toDateString() >= $day;
    }

    public function isCurrent(): bool
    {
        return $this->valid_to === null;
    }
}

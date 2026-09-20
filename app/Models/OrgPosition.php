<?php

namespace App\Models;

use App\Enums\OrgSeatStatus;
use App\Support\HasValidityDates;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgPosition extends OrganizationModel
{
    use HasValidityDates;

    protected $fillable = [
        'organization_id',
        'job_position_id',
        'department_id',
        'seat_no',
        'status',
        'person_id',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrgSeatStatus::class,
            'seat_no' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function label(): string
    {
        $job = $this->jobPosition?->name ?: 'Pozicija';
        $dept = $this->department?->name ?: ($this->jobPosition?->department?->name ?: '');

        return trim($job.' #'.$this->seat_no.($dept !== '' ? ' · '.$dept : ''));
    }
}

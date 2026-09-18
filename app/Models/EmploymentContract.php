<?php

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\EmploymentInstrument;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentContract extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'kind',
        'contract_type',
        'number',
        'signed_at',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'weekly_hours',
        'is_current',
        'note',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'kind' => EmploymentInstrument::class,
            'contract_type' => ContractType::class,
            'signed_at' => 'date',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'trial_ends_at' => 'date',
            'is_current' => 'boolean',
            'weekly_hours' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function summary(): string
    {
        $parts = [$this->kind->label()];
        if ($this->number) {
            $parts[] = $this->number;
        }
        if ($this->contract_type) {
            $parts[] = $this->contract_type->label();
        }
        $parts[] = 'od '.$this->starts_at->format('d.m.Y.');
        if ($this->ends_at) {
            $parts[] = 'do '.$this->ends_at->format('d.m.Y.');
        }
        if ($this->trial_ends_at) {
            $parts[] = 'probni do '.$this->trial_ends_at->format('d.m.Y.');
        }

        return implode(' · ', $parts);
    }
}

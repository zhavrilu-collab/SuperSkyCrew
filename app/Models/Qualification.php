<?php

namespace App\Models;

use App\Enums\QualificationKind;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qualification extends OrganizationModel
{
    protected $table = 'person_qualifications';

    protected $fillable = [
        'organization_id',
        'person_id',
        'kind',
        'title',
        'issuer',
        'issued_on',
        'expires_at',
        'required_for_job',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'kind' => QualificationKind::class,
            'issued_on' => 'date',
            'expires_at' => 'date',
            'required_for_job' => 'boolean',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function summary(): string
    {
        $parts = [$this->kind->label(), $this->title];
        if ($this->issuer) {
            $parts[] = $this->issuer;
        }
        if ($this->expires_at) {
            $parts[] = 'vrijedi do '.$this->expires_at->format('d.m.Y.');
        }

        return implode(' · ', $parts);
    }
}

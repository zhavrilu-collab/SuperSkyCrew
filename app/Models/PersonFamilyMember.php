<?php

namespace App\Models;

use App\Enums\FamilyKin;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonFamilyMember extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'first_name',
        'last_name',
        'kin',
        'date_of_birth',
        'is_dependent',
        'is_emergency_contact',
        'phone',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'kin' => FamilyKin::class,
            'date_of_birth' => 'date',
            'is_dependent' => 'boolean',
            'is_emergency_contact' => 'boolean',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}

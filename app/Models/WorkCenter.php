<?php

namespace App\Models;

use App\Support\HasValidityDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends OrganizationModel
{
    use HasFactory;
    use HasValidityDates;

    protected $fillable = [
        'organization_id',
        'legal_entity_id',
        'location_id',
        'name',
        'code',
        'street',
        'city',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function enterpriseUnits(): HasMany
    {
        return $this->hasMany(EnterpriseUnit::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function summary(): string
    {
        return $this->code ? $this->code.' · '.$this->name : $this->name;
    }
}

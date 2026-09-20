<?php

namespace App\Models;

use App\Support\HasValidityDates;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessSegment extends OrganizationModel
{
    use HasValidityDates;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'description',
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

    public function enterpriseUnits(): HasMany
    {
        return $this->hasMany(EnterpriseUnit::class);
    }

    public function summary(): string
    {
        return $this->code ? $this->name.' · '.$this->code : $this->name;
    }
}

<?php

namespace App\Models;

use App\Support\HasValidityDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalEntity extends OrganizationModel
{
    use HasFactory;
    use HasValidityDates;

    protected $fillable = [
        'organization_id',
        'parent_id',
        'name',
        'code',
        'oib',
        'street',
        'city',
        'country',
        'iban',
        'court',
        'capital',
        'signatories',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class);
    }

    public function enterpriseUnits(): HasMany
    {
        return $this->hasMany(EnterpriseUnit::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function summary(): string
    {
        $parts = [$this->name];
        if ($this->code) {
            $parts[] = $this->code;
        }
        if ($this->oib) {
            $parts[] = 'OIB '.$this->oib;
        }

        return implode(' · ', $parts);
    }
}

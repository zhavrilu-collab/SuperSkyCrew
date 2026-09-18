<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends OrganizationModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'rad1g',
        'annual_leave_days',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'annual_leave_days' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function isValidOn(Carbon $date): bool
    {
        $day = $date->toDateString();

        if ($this->valid_from && $this->valid_from->toDateString() > $day) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->toDateString() < $day) {
            return false;
        }

        return true;
    }

    public function summary(): string
    {
        $parts = [$this->name];
        if ($this->rad1g) {
            $parts[] = 'RAD1G '.$this->rad1g;
        }

        return implode(' · ', $parts);
    }
}

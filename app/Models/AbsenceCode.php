<?php

namespace App\Models;

class AbsenceCode extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'meaning',
        'category',
        'kind',
        'paid',
        'consumes_annual_leave',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'paid' => 'boolean',
            'consumes_annual_leave' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'presence' => 'Prisutnost',
            'leave' => 'Odsutnost',
            'sick' => 'Bolovanje',
            'holiday' => 'Blagdan',
            default => 'Ostalo',
        };
    }

    public function legendLine(): string
    {
        $line = $this->name;
        if ($this->meaning) {
            $line .= ' — '.$this->meaning;
        }
        if (! $this->paid) {
            $line .= ' · neplaćeno';
        }

        return $line;
    }

    public function badgeClass(): string
    {
        return match ($this->category) {
            'leave' => 'text-bg-info',
            'sick' => 'text-bg-danger',
            'holiday' => 'text-bg-secondary',
            'presence' => 'text-bg-success',
            default => 'text-bg-warning',
        };
    }
}

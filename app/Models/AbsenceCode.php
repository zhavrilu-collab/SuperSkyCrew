<?php

namespace App\Models;

class AbsenceCode extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'category',
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

    public function badgeClass(): string
    {
        return match ($this->category) {
            'leave' => 'text-bg-info',
            'sick' => 'text-bg-danger',
            'holiday' => 'text-bg-secondary',
            default => 'text-bg-warning',
        };
    }
}

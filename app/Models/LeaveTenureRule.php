<?php

namespace App\Models;

class LeaveTenureRule extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'min_years',
        'extra_days',
    ];

    protected function casts(): array
    {
        return [
            'min_years' => 'integer',
            'extra_days' => 'integer',
        ];
    }
}

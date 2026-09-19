<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class GrantProject extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GrantEntry::class);
    }

    public function label(): string
    {
        return $this->code.' · '.$this->name;
    }
}

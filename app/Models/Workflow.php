<?php

namespace App\Models;

use App\Enums\RequestType;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'type',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'is_active' => 'boolean',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(WorkflowRequest::class);
    }
}

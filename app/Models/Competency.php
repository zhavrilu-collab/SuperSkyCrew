<?php

namespace App\Models;

use App\Enums\CompetencyKind;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competency extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'name',
        'kind',
    ];

    protected function casts(): array
    {
        return [
            'kind' => CompetencyKind::class,
        ];
    }

    public function jobPositions(): BelongsToMany
    {
        return $this->belongsToMany(JobPosition::class, 'job_position_competency')
            ->withPivot(['organization_id', 'required_level'])
            ->withTimestamps();
    }
}

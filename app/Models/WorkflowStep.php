<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'workflow_id',
        'position',
        'role',
        'min_days',
        'max_days',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'min_days' => 'integer',
            'max_days' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function roleLabel(): string
    {
        return OrganizationRole::tryFrom($this->role)?->label() ?? $this->role;
    }

    public function conditionLabel(): string
    {
        if ($this->min_days !== null && $this->max_days !== null) {
            return $this->min_days.'–'.$this->max_days.' dana';
        }
        if ($this->max_days !== null) {
            return 'do '.$this->max_days.' dana';
        }
        if ($this->min_days !== null) {
            return $this->min_days.'+ dana';
        }

        return 'uvijek';
    }

    public function matches(int $days, bool $hasManager): bool
    {
        if ($this->role === OrganizationRole::Manager->value && ! $hasManager) {
            return false;
        }
        if ($this->min_days !== null && $days < $this->min_days) {
            return false;
        }
        if ($this->max_days !== null && $days > $this->max_days) {
            return false;
        }

        return true;
    }
}

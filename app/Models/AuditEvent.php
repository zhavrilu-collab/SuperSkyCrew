<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends OrganizationModel
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'actor_user_id',
        'person_id',
        'action',
        'subject_type',
        'subject_id',
        'summary',
        'meta',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function actionLabel(): string
    {
        return $this->action instanceof AuditAction
            ? $this->action->label()
            : (string) $this->action;
    }
}

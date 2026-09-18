<?php

namespace App\Models;

use App\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentHandover extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'recorded_by_user_id',
        'handed_on',
        'recipient',
        'purpose',
        'document_kind',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'handed_on' => 'date',
            'document_kind' => DocumentKind::class,
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}

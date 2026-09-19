<?php

namespace App\Models;

use App\Enums\RetentionClass;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'retention_class',
        'tracks_expiry',
        'is_system',
        'print_key',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'retention_class' => RetentionClass::class,
            'tracks_expiry' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PersonDocument::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    public function printLabel(): ?string
    {
        return match ($this->print_key) {
            'review' => 'Pisani pregled (čl. 4.)',
            'contract' => 'Ugovor o radu',
            'referral' => 'Uputnica za liječnički pregled',
            'leave_decision' => 'Rješenje o GO',
            default => null,
        };
    }
}

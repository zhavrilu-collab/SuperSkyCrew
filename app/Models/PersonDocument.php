<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PersonDocument extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'document_type_id',
        'title',
        'issued_on',
        'expires_on',
        'retain_until',
        'retention_proposed_at',
        'disposed_at',
        'disposed_by',
        'note',
        'file_path',
        'original_name',
        'mime',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
            'retain_until' => 'date',
            'retention_proposed_at' => 'datetime',
            'disposed_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function disposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    public function isDisposed(): bool
    {
        return $this->disposed_at !== null;
    }

    public function isProposed(): bool
    {
        return $this->retention_proposed_at !== null && $this->disposed_at === null;
    }

    public function label(): string
    {
        $type = $this->documentType?->name ?: 'Dokument';

        return $this->title && $this->title !== $type
            ? $type.' · '.$this->title
            : $type;
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk('local')->delete($this->file_path);
        }
    }
}

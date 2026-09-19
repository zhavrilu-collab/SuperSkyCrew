<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentTemplate extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'document_type_id',
        'name',
        'file_path',
        'original_name',
        'mime',
        'is_system',
        'kind',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function isDocx(): bool
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)) === 'docx';
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk('local')->delete($this->file_path);
        }
    }
}

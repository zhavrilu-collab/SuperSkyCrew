<?php

namespace App\Models;

use App\Enums\InternalActKind;
use Illuminate\Support\Facades\Storage;

class InternalAct extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'title',
        'kind',
        'version',
        'must_read',
        'published_at',
        'file_path',
        'original_name',
        'mime',
    ];

    protected function casts(): array
    {
        return [
            'kind' => InternalActKind::class,
            'must_read' => 'boolean',
            'published_at' => 'date',
        ];
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

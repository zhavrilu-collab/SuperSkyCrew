<?php

namespace App\Models;

use App\Support\HasValidityDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnterpriseUnit extends OrganizationModel
{
    use HasFactory;
    use HasValidityDates;

    protected $fillable = [
        'organization_id',
        'parent_id',
        'legal_entity_id',
        'work_center_id',
        'business_segment_id',
        'name',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function businessSegment(): BelongsTo
    {
        return $this->belongsTo(BusinessSegment::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function caption(): string
    {
        if ($this->legalEntity) {
            return $this->legalEntity->code ?: $this->legalEntity->name;
        }
        if ($this->workCenter) {
            return $this->workCenter->summary();
        }

        return 'poslovna jedinica';
    }
}

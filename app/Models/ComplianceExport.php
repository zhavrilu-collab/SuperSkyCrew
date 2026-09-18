<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceExport extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'kind',
        'from_date',
        'to_date',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

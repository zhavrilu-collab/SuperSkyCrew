<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends OrganizationModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'manager_user_id',
        'name',
        'code',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function isValidOn(Carbon $date): bool
    {
        $day = $date->toDateString();

        if ($this->valid_from && $this->valid_from->toDateString() > $day) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->toDateString() < $day) {
            return false;
        }

        return true;
    }
}

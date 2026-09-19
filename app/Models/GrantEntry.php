<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrantEntry extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'person_id',
        'grant_project_id',
        'work_date',
        'minutes',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'minutes' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(GrantProject::class, 'grant_project_id');
    }

    public function hoursLabel(): string
    {
        $hours = intdiv((int) $this->minutes, 60);
        $mins = ((int) $this->minutes) % 60;

        return sprintf('%d:%02d', $hours, $mins);
    }
}

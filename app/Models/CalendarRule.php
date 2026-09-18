<?php

namespace App\Models;

use App\Enums\CalendarLevel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarRule extends OrganizationModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'level',
        'department_id',
        'job_position_id',
        'person_id',
        'shift_id',
        'weekday',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'level' => CalendarLevel::class,
            'weekday' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
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

    public function weekdayLabel(): string
    {
        return [1 => 'Ponedjeljak', 2 => 'Utorak', 3 => 'Srijeda', 4 => 'Četvrtak', 5 => 'Petak', 6 => 'Subota', 7 => 'Nedjelja'][$this->weekday] ?? (string) $this->weekday;
    }

    public function scopeLabel(): string
    {
        return match ($this->level) {
            CalendarLevel::Person => $this->person?->fullName() ?: 'radnik',
            CalendarLevel::JobPosition => $this->jobPosition?->name ?: 'radno mjesto',
            CalendarLevel::Department => $this->department?->name ?: 'odjel',
            CalendarLevel::Organization => 'cijela organizacija',
        };
    }
}

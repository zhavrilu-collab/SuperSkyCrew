<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends OrganizationModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'starts_at',
        'ends_at',
        'break_minutes',
        'is_night',
    ];

    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'is_night' => 'boolean',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CalendarRule::class);
    }

    public function startsOn(Carbon $day): Carbon
    {
        return $day->copy()->startOfDay()->setTimeFromTimeString(substr((string) $this->starts_at, 0, 8));
    }

    public function endsOn(Carbon $day): Carbon
    {
        $start = $this->startsOn($day);
        $end = $day->copy()->startOfDay()->setTimeFromTimeString(substr((string) $this->ends_at, 0, 8));
        if ($end->lte($start)) {
            $end->addDay();
        }

        return $end;
    }

    public function label(): string
    {
        $code = $this->code ? $this->code.' · ' : '';

        return $code.$this->name.' '.$this->clockRange();
    }

    public function clockRange(): string
    {
        return substr((string) $this->starts_at, 0, 5).'–'.substr((string) $this->ends_at, 0, 5);
    }

    public function shortCode(): string
    {
        return $this->code ?: mb_substr($this->name, 0, 3);
    }
}

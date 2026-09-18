<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\Person;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function ensureBalance(Person $person, int $year): LeaveBalance
    {
        return LeaveBalance::query()->firstOrCreate(
            [
                'person_id' => $person->id,
                'year' => $year,
            ],
            [
                'organization_id' => $person->organization_id,
                'entitled_days' => (int) ($person->annual_leave_days ?: 20),
                'carried_days' => 0,
                'used_days' => 0,
            ],
        );
    }

    /**
     * @return array{year: int, entitled: int, carried: int, used: int, remaining_old: int, remaining_new: int, remaining: int}
     */
    public function snapshot(Person $person, ?int $year = null): array
    {
        $year ??= (int) now()->year;
        $balance = $this->ensureBalance($person, $year);

        return [
            'year' => $year,
            'entitled' => (int) $balance->entitled_days,
            'carried' => (int) $balance->carried_days,
            'used' => (int) $balance->used_days,
            'remaining_old' => $balance->remainingOld(),
            'remaining_new' => $balance->remainingNew(),
            'remaining' => $balance->remaining(),
        ];
    }

    public function assertAvailable(Person $person, int $days, int $year): void
    {
        $balance = $this->ensureBalance($person, $year);

        if ($days > $balance->remaining()) {
            throw ValidationException::withMessages([
                'to' => 'Nema dovoljno dana GO (preostalo '.$balance->remaining().', traženo '.$days.').',
            ]);
        }
    }

    public function consume(Person $person, int $days, int $year): LeaveBalance
    {
        $this->assertAvailable($person, $days, $year);
        $balance = $this->ensureBalance($person, $year);
        $balance->used_days += $days;
        $balance->save();

        return $balance;
    }
}

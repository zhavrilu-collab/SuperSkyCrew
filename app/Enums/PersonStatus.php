<?php

namespace App\Enums;

enum PersonStatus: string
{
    case Candidate = 'candidate';
    case Employee = 'employee';
    case Assigned = 'assigned';
    case OtherFo = 'other_fo';
    case Contractor = 'contractor';
    case Volunteer = 'volunteer';
    case Executive = 'executive';
    case Former = 'former';

    public function label(): string
    {
        return match ($this) {
            self::Candidate => 'Kandidat',
            self::Employee => 'Radnik',
            self::Assigned => 'Ustupljeni radnik',
            self::OtherFo => 'Druga fizička osoba',
            self::Contractor => 'Honorarac',
            self::Volunteer => 'Volonter',
            self::Executive => 'Rukovodeća osoba',
            self::Former => 'Bivši',
        };
    }

    public function usesArticleThree(): bool
    {
        return in_array($this, [self::Employee, self::Assigned, self::Executive], true);
    }

    public function usesArticleTen(): bool
    {
        return $this === self::OtherFo;
    }

    public function usesEmploymentContract(): bool
    {
        return $this->usesArticleThree();
    }

    public function clocksIn(): bool
    {
        return in_array($this, [
            self::Employee,
            self::Assigned,
            self::OtherFo,
            self::Contractor,
            self::Executive,
        ], true);
    }

    /**
     * @return list<self>
     */
    public static function selectable(bool $includeVolunteer): array
    {
        if ($includeVolunteer) {
            return self::cases();
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $status) => $status !== self::Volunteer,
        ));
    }
}

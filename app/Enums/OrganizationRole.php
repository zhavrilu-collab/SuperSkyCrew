<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Hr = 'hr';
    case Accountant = 'accountant';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Vlasnik',
            self::Hr => 'HR',
            self::Accountant => 'Računovodstvo',
            self::Manager => 'Voditelj',
            self::Employee => 'Radnik',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromMixed(string $role): ?self
    {
        return self::tryFrom($role);
    }
}

<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Na odobrenju',
            self::Approved => 'Odobreno',
            self::Rejected => 'Odbijeno',
            self::Cancelled => 'Poništeno',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-warning',
            self::Approved => 'text-bg-success',
            self::Rejected => 'text-bg-danger',
            self::Cancelled => 'text-bg-secondary',
        };
    }
}

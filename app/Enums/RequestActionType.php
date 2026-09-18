<?php

namespace App\Enums;

enum RequestActionType: string
{
    case Submit = 'submit';
    case Approve = 'approve';
    case Reject = 'reject';
    case Cancel = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::Submit => 'Podneseno',
            self::Approve => 'Odobreno',
            self::Reject => 'Odbijeno',
            self::Cancel => 'Poništeno',
        };
    }
}

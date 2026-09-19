<?php

namespace App\Enums;

enum ClockChannel: string
{
    case Pwa = 'pwa';
    case Web = 'web';
    case Kiosk = 'kiosk';
    case Manager = 'manager';
    case Workflow = 'workflow';
    case Api = 'api';
    case Entrance = 'entrance';
    case Terminal = 'terminal';
    case Chat = 'chat';

    public function label(): string
    {
        return match ($this) {
            self::Pwa => 'Mobitel',
            self::Web => 'Web',
            self::Kiosk => 'Kiosk',
            self::Manager => 'Ručni unos',
            self::Workflow => 'Radni slijed',
            self::Api => 'API',
            self::Entrance => 'Ulazni QR',
            self::Terminal => 'Terminal',
            self::Chat => 'Chat',
        };
    }
}

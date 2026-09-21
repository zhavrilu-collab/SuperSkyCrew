<?php

namespace App\Support;

class OrganizationFeatures
{
    public const CLOCK_MOBILE = 'clock_mobile';

    public const CLOCK_KIOSK = 'clock_kiosk';

    public const CLOCK_GEOFENCE = 'clock_geofence';

    public const CLOCK_TERMINAL = 'clock_terminal';

    public const CLOCK_CHAT = 'clock_chat';

    public const SHIFT_PLANNING = 'shift_planning';

    public const WORKFLOW = 'workflow';

    public const DOCUMENT_TEMPLATES = 'document_templates';

    public const INSPECTION_EXPORT = 'inspection_export';

    public const VOLUNTEER_MODULE = 'volunteer_module';

    public const GRANT_HOURS = 'grant_hours';

    public const SHIFT_BOARD = 'shift_board';

    public const PUSH = 'push';

    /** @var list<string> */
    public const KEYS = [
        self::CLOCK_MOBILE,
        self::CLOCK_KIOSK,
        self::CLOCK_GEOFENCE,
        self::CLOCK_TERMINAL,
        self::CLOCK_CHAT,
        self::SHIFT_PLANNING,
        self::WORKFLOW,
        self::DOCUMENT_TEMPLATES,
        self::INSPECTION_EXPORT,
        self::VOLUNTEER_MODULE,
        self::GRANT_HOURS,
        self::SHIFT_BOARD,
        self::PUSH,
    ];

    /** @return array<string, bool> */
    public static function defaultsForPlan(?string $plan): array
    {
        $v1 = [
            self::CLOCK_MOBILE => true,
            self::CLOCK_KIOSK => true,
            self::CLOCK_GEOFENCE => true,
            self::SHIFT_PLANNING => true,
            self::WORKFLOW => true,
            self::DOCUMENT_TEMPLATES => true,
            self::INSPECTION_EXPORT => true,
            self::VOLUNTEER_MODULE => false,
            self::CLOCK_TERMINAL => false,
            self::CLOCK_CHAT => false,
            self::GRANT_HOURS => false,
            self::SHIFT_BOARD => false,
            self::PUSH => false,
        ];

        return match ($plan) {
            'basic' => array_merge($v1, [
                self::CLOCK_KIOSK => false,
                self::CLOCK_GEOFENCE => false,
                self::INSPECTION_EXPORT => false,
                self::SHIFT_PLANNING => false,
            ]),
            'premium' => array_merge($v1, [
                self::CLOCK_TERMINAL => true,
                self::CLOCK_CHAT => true,
                self::GRANT_HOURS => true,
                self::SHIFT_BOARD => true,
                self::PUSH => true,
                self::VOLUNTEER_MODULE => true,
            ]),
            default => array_merge($v1, [
                self::CLOCK_TERMINAL => true,
                self::CLOCK_CHAT => true,
                self::GRANT_HOURS => true,
                self::SHIFT_BOARD => true,
                self::PUSH => true,
            ]),
        };
    }

    public static function defaultLimit(?string $plan): ?int
    {
        return match ($plan) {
            'basic' => 25,
            'premium' => 500,
            'standard' => 100,
            default => 100,
        };
    }

    /** @return list<string> */
    public static function planSlugs(): array
    {
        return ['basic', 'standard', 'premium'];
    }

    public static function planLabel(?string $plan): string
    {
        return match ($plan) {
            'basic' => 'Osnovni',
            'standard' => 'Standardni',
            'premium' => 'Premium',
            default => (string) $plan,
        };
    }

    public static function planSummary(?string $plan): string
    {
        $limit = self::defaultLimit($plan);

        return $limit !== null
            ? 'do '.$limit.' aktivnih osoba'
            : 'neograničeno';
    }

    public static function planSortOrder(?string $plan): int
    {
        return match ($plan) {
            'basic' => 1,
            'standard' => 2,
            'premium' => 3,
            default => 0,
        };
    }

    public static function label(string $key): string
    {
        return match ($key) {
            self::CLOCK_MOBILE => 'PWA prijava',
            self::CLOCK_KIOSK => 'Kiosk',
            self::CLOCK_GEOFENCE => 'Geofence',
            self::CLOCK_TERMINAL => 'Fizički terminal',
            self::CLOCK_CHAT => 'Chat prijava',
            self::SHIFT_PLANNING => 'Plan → šihterica',
            self::WORKFLOW => 'Radni slijed',
            self::DOCUMENT_TEMPLATES => 'Word predlošci',
            self::INSPECTION_EXPORT => 'Inspekcijski izvoz',
            self::VOLUNTEER_MODULE => 'Volonteri',
            self::GRANT_HOURS => 'Grant / projektni sati',
            self::SHIFT_BOARD => 'Otvorene smjene i zamjene',
            self::PUSH => 'Push obavijesti (PWA)',
            default => $key,
        };
    }
}

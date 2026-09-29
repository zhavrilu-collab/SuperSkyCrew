<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ImpersonationSession
{
    public const TOKEN = 'platform_impersonation_token';

    public const ADMIN_NAME = 'platform_impersonation_admin_name';

    public const ADMIN_EMAIL = 'platform_impersonation_admin_email';

    public const EXPIRES_AT = 'platform_impersonation_expires_at';

    /**
     * @param  array{name: string, email: string, token: string, expires_at?: string|null}  $payload
     */
    public static function store(array $payload): void
    {
        session([
            self::TOKEN => $payload['token'],
            self::ADMIN_NAME => $payload['name'],
            self::ADMIN_EMAIL => $payload['email'],
            self::EXPIRES_AT => $payload['expires_at'] ?? null,
        ]);
    }

    public static function clear(): void
    {
        session()->forget([
            self::TOKEN,
            self::ADMIN_NAME,
            self::ADMIN_EMAIL,
            self::EXPIRES_AT,
        ]);
    }

    public static function isActive(): bool
    {
        $token = session(self::TOKEN);

        return is_string($token) && $token !== '';
    }

    public static function token(): ?string
    {
        $token = session(self::TOKEN);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function adminLabel(): ?string
    {
        $name = session(self::ADMIN_NAME);
        $email = session(self::ADMIN_EMAIL);

        if (is_string($name) && $name !== '') {
            return $name;
        }

        if (is_string($email) && $email !== '') {
            return $email;
        }

        return null;
    }

    public static function adminEmail(): ?string
    {
        $email = session(self::ADMIN_EMAIL);

        return is_string($email) && $email !== '' ? $email : null;
    }

    public static function isExpired(): bool
    {
        $expiresAt = session(self::EXPIRES_AT);

        if (! is_string($expiresAt) || $expiresAt === '') {
            return false;
        }

        return now()->gte(Carbon::parse($expiresAt));
    }
}

<?php

namespace App\Services;

use App\Models\Person;

class ClockQrService
{
    public const PREFIX = 'hr1';

    public function ensure(Person $person): string
    {
        if (filled($person->clock_qr)) {
            return $person->clock_qr;
        }

        $person->clock_qr = $this->uniqueToken((int) $person->organization_id, $person->id);
        $person->save();

        return $person->clock_qr;
    }

    public function rotate(Person $person): string
    {
        $person->clock_qr = $this->uniqueToken((int) $person->organization_id, $person->id);
        $person->save();

        return $person->clock_qr;
    }

    public function payload(Person $person): string
    {
        $slug = $person->organization?->slug
            ?? $person->organization()->value('slug')
            ?? '';

        return self::PREFIX.':'.$slug.':'.$this->ensure($person);
    }

    public function parse(string $raw, string $slug): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $quotedSlug = preg_quote($slug, '/');
        if (preg_match('/'.preg_quote(self::PREFIX, '/').':'.$quotedSlug.':([a-f0-9]{16})/i', $raw, $match)) {
            return strtolower($match[1]);
        }

        if (preg_match('/[?&]qr=([a-f0-9]{16})(?:&|$)/i', $raw, $match)) {
            return strtolower($match[1]);
        }

        if (preg_match('/^[a-f0-9]{16}$/i', $raw)) {
            return strtolower($raw);
        }

        return null;
    }

    public function uniqueToken(int $organizationId, ?int $ignorePersonId = null): string
    {
        do {
            $token = bin2hex(random_bytes(8));
            $exists = Person::query()
                ->where('organization_id', $organizationId)
                ->where('clock_qr', $token)
                ->when($ignorePersonId, fn ($query) => $query->where('id', '!=', $ignorePersonId))
                ->exists();
        } while ($exists);

        return $token;
    }
}

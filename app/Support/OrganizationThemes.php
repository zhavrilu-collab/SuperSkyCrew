<?php

namespace App\Support;

use App\Models\Organization;

class OrganizationThemes
{
    public const DEFAULT = 'zelena';

    /**
     * @return array<string, array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string}>
     */
    public static function builtinAll(): array
    {
        return [
            'zelena' => [
                'label' => 'Zelena',
                'primary' => '#1b431c',
                'dark' => '#112b12',
                'gold' => '#d4af37',
                'light' => '#f4f8f4',
                'text' => '#2b3a2b',
                'accent' => '#8b1414',
            ],
            'plava' => [
                'label' => 'Plava',
                'primary' => '#1b3a5c',
                'dark' => '#0d2038',
                'gold' => '#5cadd4',
                'light' => '#f0f4f8',
                'text' => '#1e2d3d',
                'accent' => '#8b1414',
            ],
            'crvena' => [
                'label' => 'Crvena',
                'primary' => '#8b1a1a',
                'dark' => '#5c1010',
                'gold' => '#e8a317',
                'light' => '#fdf2f2',
                'text' => '#3a2020',
                'accent' => '#6b0f0f',
            ],
            'zuta' => [
                'label' => 'Žuta',
                'primary' => '#c9a400',
                'dark' => '#7a6400',
                'gold' => '#ffe14a',
                'light' => '#fffbe8',
                'text' => '#3d3410',
                'accent' => '#8b1414',
            ],
            'narancasta' => [
                'label' => 'Narančasta',
                'primary' => '#b5540c',
                'dark' => '#7a3708',
                'gold' => '#f2c14e',
                'light' => '#fdf6ee',
                'text' => '#4a3018',
                'accent' => '#8b1414',
            ],
        ];
    }

    public static function productLogoPath(?string $key = null, bool $horizontal = false): string
    {
        $resolved = self::resolve($key);
        $suffix = $horizontal ? '-horizontal.png' : '.png';

        return 'brand/product/'.$resolved.$suffix;
    }

    public static function productLogoUrl(?string $key = null, bool $horizontal = false): string
    {
        return asset(self::productLogoPath($key, $horizontal));
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::builtinAll());
    }

    public static function resolve(?string $key): string
    {
        $key = $key ?: self::DEFAULT;

        return array_key_exists($key, self::builtinAll()) ? $key : self::DEFAULT;
    }

    /** @return array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string} */
    public static function palette(?string $key): array
    {
        $catalog = self::builtinAll();
        $resolved = self::resolve($key);

        return $catalog[$resolved];
    }

    /** @return array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string} */
    public static function paletteFor(?Organization $organization): array
    {
        return self::palette($organization?->theme_key);
    }

    /**
     * @return array<string, array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string, focusShadow: string, tableBorder: string, horizontalLogo: string}>
     */
    public static function previewPayload(?Organization $organization = null): array
    {
        $payload = [];

        foreach (self::builtinAll() as $key => $palette) {
            $payload[$key] = [
                'label' => $palette['label'],
                'primary' => $palette['primary'],
                'dark' => $palette['dark'],
                'gold' => $palette['gold'],
                'light' => $palette['light'],
                'text' => $palette['text'],
                'accent' => $palette['accent'],
                'focusShadow' => self::cssRgba($palette['primary'], 0.15),
                'tableBorder' => self::cssRgba($palette['primary'], 0.18),
                'horizontalLogo' => self::productLogoUrl($key, true),
            ];
        }

        return $payload;
    }

    public static function cssRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return 'rgba(0,0,0,'.$alpha.')';
        }

        return sprintf(
            'rgba(%d,%d,%d,%s)',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
            rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.'),
        );
    }
}

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
                'primary' => '#b0cb1f',
                'dark' => '#434d0c',
                'gold' => '#ffd310',
                'light' => '#f7fae9',
                'text' => '#272d07',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
            ],
            'plava' => [
                'label' => 'Plava',
                'primary' => '#50abde',
                'dark' => '#1e4154',
                'gold' => '#ffd310',
                'light' => '#eef7fc',
                'text' => '#122631',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
            ],
            'crvena' => [
                'label' => 'Crvena',
                'primary' => '#e31e24',
                'dark' => '#560b0e',
                'gold' => '#ffd310',
                'light' => '#fce8e9',
                'text' => '#320708',
                'accent' => '#560b0e',
                'onPrimary' => '#ffffff',
            ],
            'zuta' => [
                'label' => 'Žuta',
                'primary' => '#ffd310',
                'dark' => '#615006',
                'gold' => '#ef7f1a',
                'light' => '#fffbe7',
                'text' => '#382e04',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
            ],
            'narancasta' => [
                'label' => 'Narančasta',
                'primary' => '#ef7f1a',
                'dark' => '#5b300a',
                'gold' => '#ffd310',
                'light' => '#fdf2e8',
                'text' => '#351c06',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
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

    /** @return list<string> */
    public static function styleKeys(): array
    {
        return ThemeRecipes::styleKeys();
    }

    public static function resolve(?string $key): string
    {
        $key = $key ?: self::DEFAULT;

        return array_key_exists($key, self::builtinAll()) ? $key : self::DEFAULT;
    }

    /** @return array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string} */
    public static function palette(?string $key, ?string $style = null): array
    {
        $resolved = self::resolve($key);

        return ThemeRecipes::apply(self::builtinAll()[$resolved], $resolved, $style);
    }

    /** @return array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string} */
    public static function paletteFor(?Organization $organization): array
    {
        $style = $organization
            ? ThemeRecipes::effectiveStyle($organization->theme_style)
            : null;

        return self::palette($organization?->theme_key, $style);
    }

    /**
     * @return array<string, array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string, focusShadow: string, tableBorder: string, horizontalLogo: string}>
     */
    public static function previewPayload(?Organization $organization = null): array
    {
        $payload = [];

        foreach (self::builtinAll() as $key => $palette) {
            $payload[$key] = self::decoratePreview($palette, $key);
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public static function clientPreview(?Organization $organization = null): array
    {
        return [
            'savedColor' => self::resolve($organization?->theme_key),
            'savedStyle' => $organization
                ? ThemeRecipes::effectiveStyle($organization->theme_style)
                : ThemeRecipes::DEFAULT_STYLE,
            'palettes' => self::previewPayload(),
            'styles' => ThemeRecipes::styles(),
            'combinations' => self::combinationPayload(),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function combinationPayload(): array
    {
        $payload = [];

        foreach (self::keys() as $color) {
            foreach (ThemeRecipes::styleKeys() as $style) {
                $payload[$color.'|'.$style] = self::decoratePreview(
                    self::palette($color, $style),
                    $color,
                );
            }
        }

        return $payload;
    }

    /** @param  array<string, mixed>  $palette */
    private static function decoratePreview(array $palette, string $colorKey): array
    {
        return [
            'label' => $palette['label'],
            'styleLabel' => $palette['styleLabel'] ?? null,
            'styled' => (bool) ($palette['styled'] ?? false),
            'primary' => $palette['primary'],
            'dark' => $palette['dark'],
            'gold' => $palette['gold'],
            'light' => $palette['light'],
            'text' => $palette['text'],
            'accent' => $palette['accent'],
            'onPrimary' => $palette['onPrimary'],
            'focusShadow' => self::cssRgba($palette['primary'], 0.15),
            'tableBorder' => self::cssRgba($palette['primary'], 0.18),
            'horizontalLogo' => self::productLogoUrl($colorKey, true),
            'logoMark' => $palette['logoMark'] ?? null,
            'navBg' => $palette['navBg'] ?? null,
            'navFg' => $palette['navFg'] ?? null,
            'navBar' => $palette['navBar'] ?? null,
            'navWeight' => $palette['navWeight'] ?? null,
            'sideBg' => $palette['sideBg'] ?? null,
            'idle' => $palette['idle'] ?? null,
            'btnBg' => $palette['btnBg'] ?? null,
            'btnFg' => $palette['btnFg'] ?? null,
            'btnBorder' => $palette['btnBorder'] ?? null,
            'btnHoverBg' => $palette['btnHoverBg'] ?? null,
            'btnHoverFg' => $palette['btnHoverFg'] ?? null,
        ];
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

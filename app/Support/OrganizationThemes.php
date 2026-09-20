<?php

namespace App\Support;

use App\Models\Organization;

class OrganizationThemes
{
    public const DEFAULT = 'tirkizna';

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
            'bordo' => [
                'label' => 'Bordo',
                'primary' => '#6b1414',
                'dark' => '#3d0b0b',
                'gold' => '#d4af37',
                'light' => '#f8f0f0',
                'text' => '#3a2020',
                'accent' => '#4a0e0e',
            ],
            'antracit' => [
                'label' => 'Antracit siva',
                'primary' => '#37424a',
                'dark' => '#1f262b',
                'gold' => '#c9ced2',
                'light' => '#f3f4f5',
                'text' => '#2a3138',
                'accent' => '#8b1414',
            ],
            'tirkizna' => [
                'label' => 'Tirkizna (zadano)',
                'primary' => '#0f6b64',
                'dark' => '#08403c',
                'gold' => '#7fd8ce',
                'light' => '#eef8f7',
                'text' => '#1a3d3a',
                'accent' => '#8b1414',
            ],
            'mornarska' => [
                'label' => 'Mornarska plava',
                'primary' => '#142850',
                'dark' => '#0a1530',
                'gold' => '#8fa8d4',
                'light' => '#eef2f8',
                'text' => '#1a2840',
                'accent' => '#8b1414',
            ],
            'smedja' => [
                'label' => 'Smeđa',
                'primary' => '#5c4326',
                'dark' => '#3a2a18',
                'gold' => '#cbb17e',
                'light' => '#f8f4ee',
                'text' => '#3d3020',
                'accent' => '#8b1414',
            ],
            'maslinasta' => [
                'label' => 'Maslinasta',
                'primary' => '#4a5a28',
                'dark' => '#2f3a18',
                'gold' => '#b8c96e',
                'light' => '#f4f6ee',
                'text' => '#2a3020',
                'accent' => '#8b1414',
            ],
            'ljubicasta' => [
                'label' => 'Ljubičasta',
                'primary' => '#3d1b5c',
                'dark' => '#210d33',
                'gold' => '#c9a4e0',
                'light' => '#f6f0fa',
                'text' => '#2d1f3a',
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
            'roza' => [
                'label' => 'Roza',
                'primary' => '#a13d63',
                'dark' => '#6b2540',
                'gold' => '#e8a6c3',
                'light' => '#fdf0f5',
                'text' => '#4a2435',
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
            'indigo' => [
                'label' => 'Indigo',
                'primary' => '#2c3478',
                'dark' => '#1a2048',
                'gold' => '#9aa8e8',
                'light' => '#f0f2fa',
                'text' => '#1e2240',
                'accent' => '#8b1414',
            ],
            'jantarna' => [
                'label' => 'Jantarna',
                'primary' => '#9a6b08',
                'dark' => '#6b4a05',
                'gold' => '#f0c040',
                'light' => '#fdf8ec',
                'text' => '#4a3818',
                'accent' => '#8b1414',
            ],
            'grafit' => [
                'label' => 'Grafit',
                'primary' => '#2d3438',
                'dark' => '#181c1f',
                'gold' => '#a8b0b5',
                'light' => '#f2f3f4',
                'text' => '#252a2e',
                'accent' => '#8b1414',
            ],
            'vinska' => [
                'label' => 'Vinska',
                'primary' => '#5c1a3a',
                'dark' => '#3a1024',
                'gold' => '#d4a0b8',
                'light' => '#faf0f4',
                'text' => '#3a2430',
                'accent' => '#8b1414',
            ],
        ];
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
     * @return array<string, array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string, focusShadow: string, tableBorder: string}>
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

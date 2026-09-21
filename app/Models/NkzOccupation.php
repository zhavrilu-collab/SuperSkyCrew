<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class NkzOccupation extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'title',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    public static function forgetCatalogCache(): void
    {
        Cache::forget('nkz-rad1g-titles');
        Cache::forget('nkz-rad1g-options');
    }

    /**
     * @return array<string, string>
     */
    public static function titleMap(): array
    {
        return Cache::remember('nkz-rad1g-titles', 86400, function () {
            return static::query()->orderBy('code')->pluck('title', 'code')->all();
        });
    }

    public static function titleFor(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $title = static::titleMap()[$code] ?? null;

        return is_string($title) && $title !== '' ? $title : null;
    }

    /**
     * @return list<array{code: string, title: string}>
     */
    public static function rad1gOptions(): array
    {
        return Cache::remember('nkz-rad1g-options', 86400, function () {
            return static::query()
                ->orderBy('code')
                ->get(['code', 'title'])
                ->map(fn (self $row) => [
                    'code' => $row->code,
                    'title' => $row->title,
                ])
                ->values()
                ->all();
        });
    }
}

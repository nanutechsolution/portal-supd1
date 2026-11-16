<?php

namespace App\Models;

use App\Support\MediaFiles;
use App\Support\PublicPageCache;
use Closure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Simple key/value store for the website settings page.
 *
 * Stored values are cached forever and only refreshed when settings are
 * saved, so public pages never query the database per component.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'portal.settings';

    /**
     * Setting keys that hold a path on the public disk.
     *
     * @var array<int, string>
     */
    private const MEDIA_KEYS = [
        'site_logo',
        'site_logo_footer',
        'site_favicon',
        'seo_og_image',
        'seo_social_image',
    ];

    /**
     * @var array<string, string|null>|null
     */
    private static ?array $stored = null;

    /**
     * @var array<string, string|null>|null
     */
    private static ?array $resolved = null;

    /**
     * Resolved settings, with config defaults used when no row exists.
     *
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = config('portal.settings', []);
        $stored = array_filter(
            static::storedValues(),
            fn (?string $value): bool => $value !== null,
        );

        return self::$resolved = array_replace($defaults, $stored);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::values()[$key] ?? $default;
    }

    /**
     * Media setting keys on the public disk.
     *
     * @return array<int, string>
     */
    public static function mediaKeys(): array
    {
        return self::MEDIA_KEYS;
    }

    /**
     * Raw values exactly as stored, without config defaults.
     *
     * Used by the media integrity guard so an empty default is never mistaken
     * for an existing file.
     *
     * @return array<string, string|null>
     */
    public static function rawValues(): array
    {
        return static::storedValues();
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = static::get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Absolute URL for an uploaded media setting, or null when unset.
     */
    public static function mediaUrl(string $key): ?string
    {
        $value = static::get($key);

        if (! filled($value)) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return url(Storage::disk('public')->url($value));
    }

    /**
     * Inline CSS overriding the compiled theme palette with the configured
     * primary and secondary colors.
     */
    public static function themeStyle(): string
    {
        $rules = [];

        $primary = static::normalizeHex(static::get('theme_primary'));
        $defaultPrimary = static::normalizeHex((string) config('portal.settings.theme_primary'));

        if ($primary !== null && $primary !== $defaultPrimary) {
            foreach (static::inkPalette($primary) as $shade => $hex) {
                $rules[] = "--color-ink-{$shade}:{$hex};";
            }
        }

        $secondary = static::normalizeHex(static::get('theme_secondary'));
        $defaultSecondary = static::normalizeHex((string) config('portal.settings.theme_secondary'));

        if ($secondary !== null && $secondary !== $defaultSecondary) {
            foreach (static::accentPalette($secondary) as $shade => $hex) {
                $rules[] = "--color-accent-{$shade}:{$hex};";
            }
        }

        return $rules === [] ? '' : ':root{'.implode('', $rules).'}';
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  (Closure(array<string, mixed>, array<string, mixed>): void)|null  $beforeCommit
     */
    public static function putMany(array $values, ?Closure $beforeCommit = null): void
    {
        $allowed = array_keys(config('portal.settings', []));
        $previous = static::storedValues();

        /** @var array<int, string> $filesToDelete */
        $filesToDelete = [];

        DB::transaction(function () use ($values, $allowed, $previous, &$filesToDelete, $beforeCommit): void {
            /** @var array<string, mixed> $changedBefore */
            $changedBefore = [];

            /** @var array<string, mixed> $changedAfter */
            $changedAfter = [];

            foreach ($values as $key => $value) {
                if (! in_array($key, $allowed, true)) {
                    continue;
                }

                if (is_array($value)) {
                    $value = $value[0] ?? null;
                }

                $normalized = match (true) {
                    is_bool($value) => $value ? '1' : '0',
                    $value === null => null,
                    default => (string) $value,
                };

                static::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $normalized],
                );

                if (($previous[$key] ?? null) !== $normalized) {
                    $changedBefore[$key] = $previous[$key] ?? null;
                    $changedAfter[$key] = $normalized;
                }

                if (in_array($key, self::MEDIA_KEYS, true)) {
                    $oldPath = $previous[$key] ?? null;

                    if (
                        filled($oldPath)
                        && $oldPath !== $normalized
                        && Str::startsWith($oldPath, 'settings/')
                    ) {
                        $filesToDelete[] = $oldPath;
                    }
                }
            }

            if ($beforeCommit) {
                $beforeCommit($changedBefore, $changedAfter);
            }
        });

        // Old files are only removed once every database write has succeeded.
        foreach (array_unique($filesToDelete) as $path) {
            MediaFiles::deleteFromPublicDisk($path, 'settings');
        }

        static::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        PublicPageCache::flush();

        self::$stored = null;
        self::$resolved = null;
    }

    /**
     * Raw values exactly as stored (no config defaults).
     *
     * @return array<string, string|null>
     */
    private static function storedValues(): array
    {
        if (self::$stored !== null) {
            return self::$stored;
        }

        try {
            self::$stored = Cache::rememberForever(
                self::CACHE_KEY,
                fn (): array => static::query()->pluck('value', 'key')->all(),
            );
        } catch (\Throwable) {
            self::$stored = [];
        }

        return self::$stored;
    }

    private static function normalizeHex(?string $value): ?string
    {
        if (! is_string($value) || preg_match('/^#[0-9a-fA-F]{6}$/', $value) !== 1) {
            return null;
        }

        return strtolower($value);
    }

    /**
     * @return array<int, string>
     */
    private static function inkPalette(string $base): array
    {
        return [
            50 => static::mix($base, '#ffffff', 0.95),
            100 => static::mix($base, '#ffffff', 0.90),
            200 => static::mix($base, '#ffffff', 0.80),
            300 => static::mix($base, '#ffffff', 0.65),
            400 => static::mix($base, '#ffffff', 0.50),
            500 => static::mix($base, '#ffffff', 0.38),
            600 => static::mix($base, '#ffffff', 0.28),
            700 => static::mix($base, '#ffffff', 0.20),
            800 => static::mix($base, '#ffffff', 0.12),
            900 => $base,
            950 => static::mix($base, '#000000', 0.25),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function accentPalette(string $base): array
    {
        return [
            50 => static::mix($base, '#ffffff', 0.92),
            100 => static::mix($base, '#ffffff', 0.82),
            200 => static::mix($base, '#ffffff', 0.66),
            300 => static::mix($base, '#ffffff', 0.45),
            400 => $base,
            500 => static::mix($base, '#000000', 0.12),
            600 => static::mix($base, '#000000', 0.24),
            700 => static::mix($base, '#000000', 0.36),
            800 => static::mix($base, '#000000', 0.46),
        ];
    }

    private static function mix(string $hex, string $target, float $weight): string
    {
        $base = array_map(
            fn (int $offset): int => (int) hexdec(substr($hex, $offset, 2)),
            [1, 3, 5],
        );

        $targetRgb = array_map(
            fn (int $offset): int => (int) hexdec(substr($target, $offset, 2)),
            [1, 3, 5],
        );

        $mixed = array_map(
            fn (int $channel, int $targetChannel): int => (int) round($channel + ($targetChannel - $channel) * $weight),
            $base,
            $targetRgb,
        );

        return sprintf('#%02x%02x%02x', ...$mixed);
    }
}

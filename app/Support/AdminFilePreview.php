<?php

namespace App\Support;

use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\UnableToCheckFileExistence;
use Illuminate\Filesystem\UnableToRetrieveMetadata;
use League\Flysystem\FilesystemException as FlysystemFilesystemException;

/**
 * URL pratinjau FileUpload untuk panel admin yang selalu sama-origin terhadap
 * halaman yang sedang dibuka (path dari konfigurasi disk publik, misalnya
 * `/storage`), bukan absolut berbasis APP_URL.
 *
 * Tanpa ini, saat admin diakses lewat origin selain APP_URL (127.0.0.1, IP
 * LAN, scheme/port berbeda), browser menolak `fetch()` preview (CORS /
 * mixed content) dan FilePond tertahan di state `busy` selamanya ("Memuat —
 * ketuk untuk membatalkan"). Metadata lain (name/size/type) identik dengan
 * bawaan Filament.
 *
 * URL publik (hero, OG image, dst.) tetap absolut berbasis APP_URL — kelas
 * ini hanya dipakai untuk pratinjau di panel admin.
 */
final class AdminFilePreview
{
    /**
     * getUploadedFileUsing() callback yang siap dipakai pada komponen FileUpload.
     *
     * @return Closure(BaseFileUpload, string, string|array<string, string>|null): ?array{name: string, size: int, type: ?string, url: ?string}
     */
    public static function uploadedFileUsing(): Closure
    {
        return fn (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array => self::uploadedFile($component, $file, $storedFileNames);
    }

    /**
     * URL `/…` yang selalu resolved terhadap origin request saat ini
     * (disk publik lokal; path dasar diambil dari konfigurasi disk).
     */
    public static function sameOriginUrl(string $path): string
    {
        $base = parse_url((string) config('filesystems.disks.public.url'), PHP_URL_PATH);

        if (! is_string($base) || $base === '') {
            $base = '/storage';
        }

        return str_replace(' ', '%20', rtrim($base, '/').'/'.ltrim($path, '/'));
    }

    /**
     * @param  string | array<string, string> | null  $storedFileNames
     * @return array{name: string, size: int, type: ?string, url: ?string} | null
     */
    public static function uploadedFile(BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        /** @var FilesystemAdapter $storage */
        $storage = $component->getDisk();

        $size = 0;
        $type = null;

        if ($component->shouldFetchFileInformation()) {
            try {
                $size = $storage->size($file);
                $type = $storage->mimeType($file);
            } catch (UnableToRetrieveMetadata|UnableToCheckFileExistence|FlysystemFilesystemException) {
                return null;
            }
        }

        return [
            'name' => ($component->isMultiple() ? (is_array($storedFileNames) ? ($storedFileNames[$file] ?? null) : null) : $storedFileNames) ?? basename($file),
            'size' => $size,
            'type' => $type,
            'url' => self::sameOriginUrl($file),
        ];
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Safe helpers for deleting media files from the public disk.
 */
final class MediaFiles
{
    /**
     * Delete a path only when it lives inside one of the expected directories.
     */
    public static function deleteFromPublicDisk(?string $path, string ...$directories): void
    {
        if (blank($path)) {
            return;
        }

        foreach ($directories as $directory) {
            $prefix = trim($directory, '/').'/';

            if (Str::startsWith($path, $prefix)) {
                Storage::disk('public')->delete($path);

                return;
            }
        }
    }
}

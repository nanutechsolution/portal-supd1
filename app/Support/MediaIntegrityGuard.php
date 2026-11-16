<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Server-side defence for the Filament FileUpload race described in H-1.
 *
 * A single-file upload field can lose its state before the form is submitted
 * (drag-and-drop replacement). The guard refuses to persist a media value that
 * disappeared while the stored record still points at an existing file, unless
 * the removal was requested explicitly by the editor.
 */
final class MediaIntegrityGuard
{
    public const REMOVAL_SUFFIX = '_remove';

    /**
     * Fields whose removal has been requested explicitly in the form state.
     *
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $fields
     * @return array<string, bool>
     */
    public static function requestedRemovals(array $state, array $fields): array
    {
        $removals = [];

        foreach ($fields as $field) {
            $removals[$field] = self::isRemovalRequested($state, $field);
        }

        return $removals;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function isRemovalRequested(array $state, string $field): bool
    {
        return (bool) ($state[self::removalFlag($field)] ?? false);
    }

    public static function removalFlag(string $field): string
    {
        return $field.self::REMOVAL_SUFFIX;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, string|null>  $persisted
     * @param  array<string, bool>  $removals
     * @return array<string, string>
     */
    public static function violations(array $state, array $persisted, array $removals = []): array
    {
        $violations = [];

        foreach ($persisted as $field => $oldPath) {
            if (blank($oldPath)) {
                continue;
            }

            $value = self::firstMediaValue($state[$field] ?? null);
            $hasValue = self::hasMediaValue($value);
            $hasReplacement = self::isReplacement($value, $oldPath);
            $isRemovalRequested = (bool) ($removals[$field] ?? false);

            if ($isRemovalRequested && $hasReplacement) {
                $violations[$field] = 'Batalkan opsi hapus media atau kosongkan unggahan pengganti sebelum menyimpan.';

                continue;
            }

            if (! $isRemovalRequested && ! $hasValue) {
                $violations[$field] = 'Media lama hilang dari formulir. Unggah ulang media pengganti, atau aktifkan opsi hapus media jika memang ingin menghapusnya.';
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, string|null>  $persisted
     * @param  array<string, bool>  $removals
     *
     * @throws ValidationException
     */
    public static function assertPreserved(array $state, array $persisted, array $removals = []): void
    {
        $violations = self::violations($state, $persisted, $removals);

        if ($violations === []) {
            return;
        }

        $messages = [];

        foreach ($violations as $field => $message) {
            $messages["data.{$field}"] = $message;
        }

        throw ValidationException::withMessages($messages);
    }

    /**
     * First usable value from a single-file FileUpload state.
     */
    public static function firstMediaValue(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::hasMediaValue($item)) {
                    return $item;
                }
            }

            return null;
        }

        return self::hasMediaValue($value) ? $value : null;
    }

    /**
     * Whether the submitted state still contains a usable media value.
     */
    public static function hasMediaValue(mixed $value): bool
    {
        if ($value instanceof UploadedFile) {
            return true;
        }

        return is_string($value) && filled($value);
    }

    /**
     * A replacement is a newly uploaded file or a different stored path.
     */
    public static function isReplacement(mixed $value, string $oldPath): bool
    {
        if ($value instanceof UploadedFile) {
            return true;
        }

        return is_string($value) && filled($value) && $value !== $oldPath;
    }
}

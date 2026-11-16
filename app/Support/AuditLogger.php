<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Bulletin;
use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Central writer for activity records.
 *
 * Callers are responsible for running this inside the same database
 * transaction as the business write. The observer-based model hooks already
 * run inside Filament's transaction when the panel enables transactions.
 */
final class AuditLogger
{
    /**
     * Key segments that always identify a credential, matching whole segments
     * only so that benign fields such as "author" or "secretary" survive.
     *
     * @var array<int, string>
     */
    private const SENSITIVE_KEY_SEGMENTS = [
        'password',
        'passwd',
        'passphrase',
        'passcode',
        'pwd',
        'token',
        'tokens',
        'secret',
        'secrets',
        'credential',
        'credentials',
        'cookie',
        'cookies',
        'authorization',
        'authorisation',
    ];

    /**
     * Compound key patterns that only read as a credential once separators or
     * camelCase boundaries are removed, such as "api_key" or "X-Api-Key".
     *
     * @var array<int, string>
     */
    private const SENSITIVE_KEY_PATTERNS = [
        'apikey',
        'sessionid',
        'privatekey',
    ];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(
        string $event,
        string $module,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        string $status = 'success',
        ?string $subjectLabel = null,
        ?User $actor = null,
    ): void {
        if (! config('audit.enabled', true)) {
            return;
        }

        $actor ??= Auth::user();
        $actorId = $actor?->getKey();

        if ($actor instanceof Model && ! $actor->exists) {
            // The actor row is already gone (for example during self-deletion),
            // so keep the name snapshot without violating the user foreign key.
            $actorId = null;
        }

        $request = app()->bound('request') ? request() : null;

        AuditLog::query()->create([
            'user_id' => $actorId,
            'actor_name' => $actor?->name,
            'event' => $event,
            'module' => $module,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subjectLabel ?? self::subjectLabel($subject),
            'before' => self::sanitize($before),
            'after' => self::sanitize($after),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), (int) config('audit.user_agent_limit', 500), ''),
            'status' => $status,
        ]);
    }

    public static function moduleFor(Model $model): string
    {
        return match (true) {
            $model instanceof Post => 'posts',
            $model instanceof Bulletin => 'bulletins',
            $model instanceof GalleryItem => 'gallery',
            $model instanceof Category => 'categories',
            $model instanceof OrganizationalUnit => 'organizational_units',
            $model instanceof Page => 'pages',
            $model instanceof User => 'users',
            default => 'other',
        };
    }

    public static function subjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        foreach (['title', 'name', 'slug'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && filled($value)) {
                return Str::limit($value, 250, '…');
            }
        }

        return '#'.$subject->getKey();
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    public static function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return self::sanitizeArray($values, 0);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function sanitizeArray(array $values, int $depth): array
    {
        if ($depth >= (int) config('audit.max_depth', 4)) {
            return ['_truncated' => 'Kedalaman data melebihi batas audit.'];
        }

        $clean = [];

        foreach ($values as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                continue;
            }

            if ($value instanceof BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof UnitEnum) {
                $value = $value->name;
            } elseif ($value instanceof DateTimeInterface) {
                $value = $value->format(DATE_ATOM);
            } elseif ($value instanceof Model) {
                $value = $value->getKey();
            }

            if (is_array($value)) {
                $value = self::sanitizeArray($value, $depth + 1);
            } elseif (is_string($value)) {
                $value = Str::limit($value, (int) config('audit.max_value_length', 5000), '…[truncated]');
            } elseif (is_object($value)) {
                $value = (string) $value;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private static function isSensitiveKey(string $key): bool
    {
        // Split on separators and camelCase boundaries before matching, so
        // "accessToken", "access_token", and "ACCESS-TOKEN" behave the same.
        $segmented = preg_replace('/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $key) ?? $key;

        $segments = preg_split('/[^a-z0-9]+/i', Str::lower($segmented)) ?: [];

        foreach ($segments as $segment) {
            if (in_array($segment, self::SENSITIVE_KEY_SEGMENTS, true)) {
                return true;
            }
        }

        $compact = preg_replace('/[^a-z0-9]/', '', Str::lower($key)) ?? '';

        if ($compact === '') {
            return false;
        }

        foreach (self::SENSITIVE_KEY_PATTERNS as $pattern) {
            if (str_contains($compact, $pattern)) {
                return true;
            }
        }

        return false;
    }
}

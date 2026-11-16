<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Models\Bulletin;
use App\Models\GalleryItem;
use App\Models\OrganizationalUnit;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Records Eloquent model changes so direct model operations, Filament pages,
 * and bulk actions all produce the same audit trail.
 */
final class ModelAuditObserver
{
    public function created(Model $model): void
    {
        $after = $this->sanitize(Arr::except($model->getAttributes(), ['id', 'created_at', 'updated_at']));

        if ($after === []) {
            return;
        }

        $this->write($model, 'created', null, $after);
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        $event = $this->updatedEvent($model, $changes);
        $before = [];
        $after = [];

        foreach (array_keys($changes) as $attribute) {
            $before[$attribute] = $model->getRawOriginal($attribute);
            $after[$attribute] = $model->getAttributes()[$attribute] ?? null;
        }

        $before = $this->sanitize($before);
        $after = $this->sanitize($after);

        if ($before === [] && $after === []) {
            return;
        }

        $this->write($model, $event, $before, $after);
    }

    public function deleted(Model $model): void
    {
        $before = $this->sanitize(Arr::except($model->getAttributes(), ['id', 'created_at', 'updated_at']));

        if ($before === []) {
            return;
        }

        $this->write($model, 'deleted', $before, null);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function updatedEvent(Model $model, array $changes): string
    {
        if ($model instanceof Post && array_key_exists('status', $changes)) {
            $old = $model->getRawOriginal('status');
            $new = $changes['status'];

            if ($new === PostStatus::Published->value && $old !== $new) {
                return 'published';
            }

            if ($old === PostStatus::Published->value && $new !== $old) {
                return 'unpublished';
            }
        }

        if (
            ($model instanceof Bulletin || $model instanceof OrganizationalUnit)
            && array_key_exists('is_published', $changes)
        ) {
            return (bool) $changes['is_published'] ? 'published' : 'unpublished';
        }

        if ($model instanceof User && array_key_exists('role', $changes)) {
            return 'role_changed';
        }

        $mediaFields = $this->mediaFields($model);
        $changedFields = array_keys($changes);

        if (
            $mediaFields !== []
            && array_intersect($changedFields, $mediaFields) !== []
            && array_diff($changedFields, $mediaFields) === []
        ) {
            return 'media_replaced';
        }

        return 'updated';
    }

    /**
     * @return array<int, string>
     */
    private function mediaFields(Model $model): array
    {
        return match (true) {
            $model instanceof Post => ['featured_image'],
            $model instanceof Bulletin => ['cover', 'file_path'],
            $model instanceof GalleryItem => ['image'],
            $model instanceof OrganizationalUnit => ['photo'],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function write(Model $model, string $event, ?array $before, ?array $after): void
    {
        AuditLogger::record(
            event: $event,
            module: AuditLogger::moduleFor($model),
            subject: $model,
            before: $before,
            after: $after,
            subjectLabel: AuditLogger::subjectLabel($model),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        return AuditLogger::sanitize($values) ?? [];
    }
}

<?php

namespace App\Filament\Resources\Bulletins\Pages;

use App\Filament\Resources\Bulletins\BulletinResource;
use App\Support\MediaIntegrityGuard;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBulletin extends EditRecord
{
    protected static string $resource = BulletinResource::class;

    /**
     * @var array<int, string>
     */
    private const MEDIA_FIELDS = ['cover', 'file_path'];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->databaseTransaction(),
        ];
    }

    protected function beforeValidate(): void
    {
        MediaIntegrityGuard::assertPreserved(
            state: $this->data ?? [],
            persisted: $this->persistedMedia(),
            removals: MediaIntegrityGuard::requestedRemovals($this->data ?? [], self::MEDIA_FIELDS),
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (self::MEDIA_FIELDS as $field) {
            if (MediaIntegrityGuard::isRemovalRequested($this->data ?? [], $field)) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, string|null>
     */
    private function persistedMedia(): array
    {
        $media = [];

        foreach (self::MEDIA_FIELDS as $field) {
            $value = $this->getRecord()->getAttribute($field);
            $media[$field] = is_string($value) ? $value : null;
        }

        return $media;
    }
}

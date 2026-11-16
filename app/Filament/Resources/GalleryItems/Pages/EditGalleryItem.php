<?php

namespace App\Filament\Resources\GalleryItems\Pages;

use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Support\MediaIntegrityGuard;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryItem extends EditRecord
{
    protected static string $resource = GalleryItemResource::class;

    /**
     * @var array<int, string>
     */
    private const MEDIA_FIELDS = ['image'];

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

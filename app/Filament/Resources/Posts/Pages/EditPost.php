<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Support\MediaIntegrityGuard;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    /**
     * @var array<int, string>
     */
    private const MEDIA_FIELDS = ['featured_image'];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pratinjau')
                ->label('Pratinjau')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => route('posts.preview', $this->getRecord()))
                ->openUrlInNewTab(),
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

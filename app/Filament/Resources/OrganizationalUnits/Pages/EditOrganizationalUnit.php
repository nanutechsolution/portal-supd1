<?php

namespace App\Filament\Resources\OrganizationalUnits\Pages;

use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Models\OrganizationalUnit;
use App\Support\MediaIntegrityGuard;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationalUnit extends EditRecord
{
    protected static string $resource = OrganizationalUnitResource::class;

    /**
     * @var array<int, string>
     */
    private const MEDIA_FIELDS = ['photo'];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->databaseTransaction()
                ->disabled(fn (OrganizationalUnit $record): bool => $record->children()->exists())
                ->tooltip(fn (OrganizationalUnit $record): ?string => $record->children()->exists()
                    ? 'Unit ini masih memiliki unit bawahan. Pindahkan atau hapus unit bawahan terlebih dahulu.'
                    : null),
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

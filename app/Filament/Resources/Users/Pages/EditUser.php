<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $isChangingOwnPassword = $this->getRecord()->getKey() === auth()->id();

        if (array_key_exists('password', $data) && ! $isChangingOwnPassword) {
            // Resetting another user's password forces them to choose their
            // own password before they can keep using the panel.
            $data['must_change_password'] = true;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->databaseTransaction(),
        ];
    }
}

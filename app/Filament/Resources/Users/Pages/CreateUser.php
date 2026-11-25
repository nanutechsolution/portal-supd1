<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // New accounts created by an administrator start with a temporary
        // password and must set their own password before entering the panel.
        $data['must_change_password'] = true;

        return $data;
    }
}

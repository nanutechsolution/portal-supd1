<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_unless(ContactMessageResource::canAccess(), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ContactMessageResource::markReadAction(),
            ContactMessageResource::markRepliedAction(),
            DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus pesan kontak?')
                ->modalDescription('Pesan yang dihapus tidak dapat dikembalikan. Tindakan ini dicatat di audit log.')
                ->before(fn (ContactMessage $record) => ContactMessageResource::auditDeletion($record))
                ->successRedirectUrl(ContactMessageResource::getUrl('index')),
            Action::make('back')
                ->label('Kembali ke daftar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ContactMessageResource::getUrl('index')),
        ];
    }
}

<?php

namespace App\Filament\Resources\ContactMessages;

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Models\ContactMessage;
use App\Support\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Pesan Kontak';

    protected static ?string $modelLabel = 'pesan kontak';

    protected static ?string $pluralModelLabel = 'pesan kontak';

    protected static string|UnitEnum|null $navigationGroup = 'Situs';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Pengirim')
                    ->searchable()
                    ->description(fn (ContactMessage $record): string => $record->email),
                TextColumn::make('subject')
                    ->label('Subjek')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ContactMessageStatus $state): string => $state->label())
                    ->color(fn (ContactMessageStatus $state): string => $state->color())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ContactMessageStatus::cases())->mapWithKeys(
                        fn (ContactMessageStatus $status): array => [$status->value => $status->label()]
                    )->all()),
                Filter::make('created_at')
                    ->label('Rentang tanggal')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari tanggal')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                self::markReadAction()->iconButton(),
                self::markRepliedAction()->iconButton(),
                DeleteAction::make()
                    ->label('Hapus')
                    ->iconButton()
                    ->modalHeading('Hapus pesan kontak?')
                    ->modalDescription('Pesan yang dihapus tidak dapat dikembalikan. Tindakan ini dicatat di audit log.')
                    ->before(fn (ContactMessage $record) => self::auditDeletion($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markRead')
                        ->label('Tandai dibaca')
                        ->icon(Heroicon::OutlinedEnvelopeOpen)
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai pesan terpilih sebagai dibaca')
                        ->modalDescription('Status pesan yang sudah dibalas tidak berubah.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, BulkAction $action): void {
                            foreach ($records as $record) {
                                if ($record->status === ContactMessageStatus::Baru) {
                                    self::updateStatus($record, ContactMessageStatus::Dibaca);
                                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                                }
                            }
                        }),
                    BulkAction::make('markReplied')
                        ->label('Tandai dibalas')
                        ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai pesan terpilih sebagai sudah dibalas')
                        ->modalDescription('Gunakan setelah balasan dikirim melalui email. Tindakan ini dicatat di audit log.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, BulkAction $action): void {
                            foreach ($records as $record) {
                                if ($record->status !== ContactMessageStatus::Dibalas) {
                                    self::updateStatus($record, ContactMessageStatus::Dibalas);
                                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                                }
                            }
                        }),
                    BulkAction::make('deleteSelected')
                        ->label('Hapus terpilih')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus pesan terpilih?')
                        ->modalDescription('Semua pesan yang dipilih akan dihapus permanen. Tindakan ini dicatat di audit log.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, BulkAction $action): void {
                            foreach ($records as $record) {
                                self::auditDeletion($record);
                                $record->delete();
                                $action->reportBulkProcessingSuccessfulRecordsCount(1);
                            }
                        }),
                ]),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pengirim')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama'),
                    TextEntry::make('email')
                        ->label('Email')
                        ->copyable()
                        ->url(fn (ContactMessage $record): string => 'mailto:'.$record->email),
                    TextEntry::make('phone')
                        ->label('Telepon')
                        ->placeholder('—'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (ContactMessageStatus $state): string => $state->label())
                        ->color(fn (ContactMessageStatus $state): string => $state->color()),
                    TextEntry::make('created_at')
                        ->label('Diterima')
                        ->dateTime('d M Y H:i'),
                    TextEntry::make('ip_address')
                        ->label('Alamat IP')
                        ->placeholder('—'),
                    TextEntry::make('user_agent')
                        ->label('User agent')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
            Section::make('Isi Pesan')
                ->schema([
                    TextEntry::make('subject')
                        ->label('Subjek')
                        ->weight('bold'),
                    TextEntry::make('message')
                        ->hiddenLabel()
                        ->prose()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = ContactMessage::query()->new()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }

    public static function markReadAction(): Action
    {
        return Action::make('markRead')
            ->label('Tandai dibaca')
            ->icon(Heroicon::OutlinedEnvelopeOpen)
            ->color('info')
            ->visible(fn (ContactMessage $record): bool => $record->status === ContactMessageStatus::Baru)
            ->requiresConfirmation()
            ->modalHeading('Tandai pesan ini sebagai dibaca?')
            ->action(fn (ContactMessage $record) => self::updateStatus($record, ContactMessageStatus::Dibaca));
    }

    public static function markRepliedAction(): Action
    {
        return Action::make('markReplied')
            ->label('Tandai dibalas')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->visible(fn (ContactMessage $record): bool => $record->status !== ContactMessageStatus::Dibalas)
            ->requiresConfirmation()
            ->modalHeading('Tandai pesan ini sebagai sudah dibalas?')
            ->modalDescription('Pastikan balasan sudah dikirim ke alamat email pengirim.')
            ->action(fn (ContactMessage $record) => self::updateStatus($record, ContactMessageStatus::Dibalas));
    }

    public static function updateStatus(ContactMessage $record, ContactMessageStatus $status): void
    {
        $previous = $record->status;

        if ($previous === $status) {
            return;
        }

        $record->update(['status' => $status]);

        AuditLogger::record(
            event: 'updated',
            module: 'contact_messages',
            subject: $record,
            subjectLabel: $record->subject,
            before: ['status' => $previous?->value],
            after: ['status' => $status->value],
        );
    }

    public static function auditDeletion(ContactMessage $record): void
    {
        AuditLogger::record(
            event: 'deleted',
            module: 'contact_messages',
            subject: $record,
            subjectLabel: $record->subject,
            before: [
                'name' => $record->name,
                'email' => $record->email,
                'status' => $record->status?->value,
            ],
        );
    }
}

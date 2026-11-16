<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
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
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $modelLabel = 'audit log';

    protected static ?string $pluralModelLabel = 'audit log';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'subject_label';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('actor_name')
                    ->label('Pengguna')
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('event')
                    ->label('Aktivitas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AuditLog::eventLabel($state))
                    ->color(fn (string $state): string => match ($state) {
                        'deleted', 'login_failed' => 'danger',
                        'published', 'login' => 'success',
                        'unpublished', 'logout' => 'warning',
                        'role_changed' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('module')
                    ->label('Modul')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AuditLog::moduleLabel($state))
                    ->toggleable(),
                TextColumn::make('subject_label')
                    ->label('Objek')
                    ->placeholder('—')
                    ->searchable()
                    ->limit(45),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AuditLog::statusLabel($state))
                    ->color(fn (string $state): string => $state === 'failed' ? 'danger' : 'success'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Pengguna')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('module')
                    ->label('Modul')
                    ->options(AuditLog::MODULE_LABELS),
                SelectFilter::make('event')
                    ->label('Aktivitas')
                    ->options(AuditLog::EVENT_LABELS),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AuditLog::STATUS_LABELS),
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
                ViewAction::make(),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ringkasan Aktivitas')
                ->columns(3)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Waktu')
                        ->dateTime('d M Y H:i:s'),
                    TextEntry::make('actor_name')
                        ->label('Pengguna')
                        ->placeholder('Sistem'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => AuditLog::statusLabel($state))
                        ->color(fn (string $state): string => $state === 'failed' ? 'danger' : 'success'),
                    TextEntry::make('event')
                        ->label('Aktivitas')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => AuditLog::eventLabel($state)),
                    TextEntry::make('module')
                        ->label('Modul')
                        ->formatStateUsing(fn (string $state): string => AuditLog::moduleLabel($state)),
                    TextEntry::make('subject_label')
                        ->label('Objek')
                        ->placeholder('—'),
                    TextEntry::make('subject_type')
                        ->label('Tipe objek')
                        ->placeholder('—'),
                    TextEntry::make('subject_id')
                        ->label('ID objek')
                        ->placeholder('—'),
                    TextEntry::make('ip_address')
                        ->label('Alamat IP')
                        ->placeholder('—'),
                    TextEntry::make('user_agent')
                        ->label('User agent')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
            Section::make('Nilai Sebelum')
                ->schema([
                    KeyValueEntry::make('before')
                        ->hiddenLabel()
                        ->keyLabel('Field')
                        ->valueLabel('Nilai')
                        ->state(fn (AuditLog $record): array => $record->before ?? [])
                        ->placeholder('Tidak ada data sebelum perubahan.'),
                ]),
            Section::make('Nilai Sesudah')
                ->schema([
                    KeyValueEntry::make('after')
                        ->hiddenLabel()
                        ->keyLabel('Field')
                        ->valueLabel('Nilai')
                        ->state(fn (AuditLog $record): array => $record->after ?? [])
                        ->placeholder('Tidak ada data sesudah perubahan.'),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}

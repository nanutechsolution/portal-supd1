<?php

namespace App\Filament\Resources\Bulletins;

use App\Filament\Resources\Bulletins\Pages\CreateBulletin;
use App\Filament\Resources\Bulletins\Pages\EditBulletin;
use App\Filament\Resources\Bulletins\Pages\ListBulletins;
use App\Models\Bulletin;
use App\Support\AdminFilePreview;
use App\Support\MediaIntegrityGuard;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class BulletinResource extends Resource
{
    protected static ?string $model = Bulletin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Buletin';

    protected static ?string $modelLabel = 'buletin';

    protected static ?string $pluralModelLabel = 'buletin';

    protected static string|UnitEnum|null $navigationGroup = 'Publikasi';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Buletin')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Buletin')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('edition_number')
                        ->label('Nomor Edisi')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('Contoh: 01'),
                    TextInput::make('year')
                        ->label('Tahun')
                        ->required()
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue((int) now()->addYear()->format('Y'))
                        ->default((int) now()->format('Y')),
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    DateTimePicker::make('published_at')
                        ->label('Tanggal Publikasi')
                        ->seconds(false)
                        ->default(now()),
                    Toggle::make('is_published')
                        ->label('Terbitkan')
                        ->helperText('Buletin hanya tampil di website jika diaktifkan.'),
                ]),
            Section::make('Berkas')
                ->schema([
                    FileUpload::make('cover')
                        ->label('Cover Buletin')
                        ->image()
                        ->disk('public')
                        ->directory('bulletins/covers')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(4096)
                        ->imageEditor()
                        ->getUploadedFileUsing(AdminFilePreview::uploadedFileUsing())
                        ->helperText('Format JPG, PNG, atau WebP. Maksimal 4 MB.'),
                    Toggle::make(MediaIntegrityGuard::removalFlag('cover'))
                        ->label('Hapus cover buletin')
                        ->helperText('Aktifkan hanya jika cover memang ingin dihapus. Opsi ini tidak diperlukan saat mengganti cover.')
                        ->visible(fn (?Bulletin $record): bool => filled($record?->cover))
                        ->dehydrated(false),
                    FileUpload::make('file_path')
                        ->label('File PDF')
                        ->disk('public')
                        ->directory('bulletins/pdf')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480)
                        ->getUploadedFileUsing(AdminFilePreview::uploadedFileUsing())
                        ->helperText('Hanya PDF. Maksimal 20 MB.'),
                    Toggle::make(MediaIntegrityGuard::removalFlag('file_path'))
                        ->label('Hapus file PDF')
                        ->helperText('Aktifkan hanya jika file PDF memang ingin dihapus. Opsi ini tidak diperlukan saat mengganti file.')
                        ->visible(fn (?Bulletin $record): bool => filled($record?->file_path))
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label('Cover')
                    ->disk('public')
                    ->height(60)
                    ->width(46),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('edition_number')
                    ->label('Edisi')
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Terbit')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Tanggal Publikasi')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('downloads')
                    ->label('Unduhan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn (): array => Bulletin::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->all()),
                TernaryFilter::make('is_published')
                    ->label('Status terbit'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->databaseTransaction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->databaseTransaction(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBulletins::route('/'),
            'create' => CreateBulletin::route('/create'),
            'edit' => EditBulletin::route('/{record}/edit'),
        ];
    }
}

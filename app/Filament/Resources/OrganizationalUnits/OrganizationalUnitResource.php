<?php

namespace App\Filament\Resources\OrganizationalUnits;

use App\Enums\OrganizationalLevel;
use App\Filament\Resources\OrganizationalUnits\Pages\CreateOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\EditOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\ListOrganizationalUnits;
use App\Models\OrganizationalUnit;
use App\Rules\ParentUnitIsNotDescendant;
use App\Support\AdminFilePreview;
use App\Support\MediaIntegrityGuard;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrganizationalUnitResource extends Resource
{
    protected static ?string $model = OrganizationalUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Struktur Organisasi';

    protected static ?string $modelLabel = 'unit organisasi';

    protected static ?string $pluralModelLabel = 'unit organisasi';

    protected static string|UnitEnum|null $navigationGroup = 'Situs';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Unit Organisasi')
                ->description('Isi nama jabatan atau unit. Hubungan atasan-bawahan hanya diisi setelah struktur resmi dikonfirmasi.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama jabatan atau unit')
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),
                    TextInput::make('official_name')
                        ->label('Nama pejabat')
                        ->maxLength(150)
                        ->helperText('Kosongkan jika nama pejabat belum tersedia atau belum dikonfirmasi.'),
                    Select::make('level')
                        ->label('Level jabatan')
                        ->options(OrganizationalLevel::options())
                        ->required()
                        ->native(false)
                        ->placeholder('Pilih level jabatan')
                        ->helperText('Menentukan warna dan bentuk kartu pada bagan publik.'),
                    Select::make('parent_id')
                        ->label('Atasan / unit induk')
                        ->options(fn (?OrganizationalUnit $record): array => OrganizationalUnit::query()
                            ->when($record, fn (Builder $query) => $query
                                ->whereKeyNot($record->getKey())
                                ->whereNotIn('id', $record->descendantIds()))
                            ->ordered()
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->placeholder('Tidak ada (unit puncak)')
                        ->helperText('Kosongkan untuk unit paling atas. Unit turunan tidak dapat dipilih sebagai atasan.')
                        ->rule(fn (?OrganizationalUnit $record): ParentUnitIsNotDescendant => new ParentUnitIsNotDescendant($record?->getKey())),
                    TextInput::make('sort_order')
                        ->label('Urutan tampilan')
                        ->numeric()
                        ->default(0)
                        ->helperText('Semakin kecil angkanya, semakin awal tampil pada bagan.'),
                    Textarea::make('description')
                        ->label('Deskripsi atau tugas')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    FileUpload::make('photo')
                        ->label('Foto pejabat')
                        ->image()
                        ->disk('public')
                        ->directory('structure')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(2048)
                        ->imageEditor()
                        ->getUploadedFileUsing(AdminFilePreview::uploadedFileUsing())
                        ->helperText('Format JPG, PNG, atau WebP. Maksimal 2 MB. Opsional.')
                        ->columnSpanFull(),
                    Toggle::make(MediaIntegrityGuard::removalFlag('photo'))
                        ->label('Hapus foto pejabat')
                        ->helperText('Aktifkan hanya jika foto memang ingin dihapus. Opsi ini tidak diperlukan saat mengganti foto.')
                        ->visible(fn (?OrganizationalUnit $record): bool => filled($record?->photo))
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
            Section::make('Publikasi')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Unit tidak aktif disembunyikan dari website publik.'),
                    Toggle::make('is_published')
                        ->label('Terbitkan di website')
                        ->default(false)
                        ->helperText('Aktifkan setelah struktur dan nama pejabat dikonfirmasi.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->height(40)
                    ->width(40),
                TextColumn::make('name')
                    ->label('Jabatan / Unit')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('official_name')
                    ->label('Pejabat')
                    ->placeholder('Belum diisi')
                    ->searchable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->placeholder('Belum diisi')
                    ->formatStateUsing(fn (?OrganizationalLevel $state): string => $state?->label() ?? 'Belum diisi')
                    ->color(fn (?OrganizationalLevel $state): string => $state?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Atasan')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('children_count')
                    ->label('Bawahan')
                    ->counts('children')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Terbit')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('level')
                    ->label('Level jabatan')
                    ->options(OrganizationalLevel::options()),
                SelectFilter::make('parent_id')
                    ->label('Unit induk')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Status aktif'),
                TernaryFilter::make('is_published')
                    ->label('Status publikasi'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->databaseTransaction()
                    ->disabled(fn (OrganizationalUnit $record): bool => $record->children()->exists())
                    ->tooltip(fn (OrganizationalUnit $record): ?string => $record->children()->exists()
                        ? 'Unit ini masih memiliki unit bawahan. Pindahkan atau hapus unit bawahan terlebih dahulu.'
                        : null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationalUnits::route('/'),
            'create' => CreateOrganizationalUnit::route('/create'),
            'edit' => EditOrganizationalUnit::route('/{record}/edit'),
        ];
    }
}

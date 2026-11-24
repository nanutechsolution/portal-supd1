<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Post;
use App\Support\AdminFilePreview;
use App\Support\MediaIntegrityGuard;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Berita';

    protected static ?string $modelLabel = 'berita';

    protected static ?string $pluralModelLabel = 'berita';

    protected static string|UnitEnum|null $navigationGroup = 'Publikasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Isi Berita')
                ->description('Tulis judul, ringkasan, dan isi berita.')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Berita')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation): void {
                            if ($operation === 'create' && blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        })
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->label('Slug URL')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->prefix('/berita/')
                        ->helperText('Dibuat otomatis dari judul jika dikosongkan.'),
                    Textarea::make('excerpt')
                        ->label('Ringkasan')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Tampil pada kartu berita dan hasil pencarian. Maksimal 500 karakter.')
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->label('Isi Berita')
                        ->required()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('posts/content')
                        ->fileAttachmentsAcceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->fileAttachmentsMaxSize(4096)
                        ->columnSpanFull(),
                ]),
            Section::make('Publikasi')
                ->description('Atur kategori, penulis, dan status terbit.')
                ->schema([
                    Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('user_id')
                        ->label('Penulis')
                        ->relationship('author', 'name')
                        ->default(fn (): ?int => auth()->id())
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->label('Status')
                        ->options(collect(PostStatus::cases())->mapWithKeys(
                            fn (PostStatus $status): array => [$status->value => $status->label()]
                        )->all())
                        ->default(PostStatus::Draft->value)
                        ->required()
                        ->native(false),
                    DateTimePicker::make('published_at')
                        ->label('Tanggal Publikasi')
                        ->seconds(false)
                        ->default(now())
                        ->helperText('Kosongkan untuk mengisi otomatis saat berita diterbitkan.'),
                    Toggle::make('is_featured')
                        ->label('Berita Unggulan')
                        ->helperText('Tampil pada bagian utama halaman depan.')
                        ->columnSpanFull(),
                    Toggle::make('is_breaking')
                        ->label('Breaking News')
                        ->helperText('Tampil paling depan pada baris "Terkini" di beranda setelah berita terbit.')
                        ->columnSpanFull(),
                ]),
            Section::make('Gambar Utama')
                ->schema([
                    FileUpload::make('featured_image')
                        ->label('Gambar Utama')
                        ->image()
                        ->disk('public')
                        ->directory('posts')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(4096)
                        ->imageEditor()
                        ->getUploadedFileUsing(AdminFilePreview::uploadedFileUsing())
                        ->helperText('Format JPG, PNG, atau WebP. Maksimal 4 MB. Rasio disarankan 16:9.')
                        ->columnSpanFull(),
                    Toggle::make(MediaIntegrityGuard::removalFlag('featured_image'))
                        ->label('Hapus gambar utama')
                        ->helperText('Aktifkan hanya jika gambar ini memang ingin dihapus. Opsi ini tidak diperlukan saat mengganti gambar.')
                        ->visible(fn (?Post $record): bool => filled($record?->featured_image))
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Gambar')
                    ->disk('public')
                    ->imageHeight(44)
                    ->width(66),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(65)
                    ->description(fn (Post $record): string => $record->excerpt ?? ''),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PostStatus $state, Post $record): string => $record->status === PostStatus::Published && $record->published_at?->isFuture() ? 'Terjadwal' : $state->label())
                    ->color(fn (PostStatus $state, Post $record): string => $record->status === PostStatus::Published && $record->published_at?->isFuture() ? 'warning' : $state->color())
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_breaking')
                    ->label('Breaking')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Penulis')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Belum terbit'),
                TextColumn::make('views')
                    ->label('Dibaca')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(PostStatus::cases())->mapWithKeys(
                        fn (PostStatus $status): array => [$status->value => $status->label()]
                    )->all()),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_featured')
                    ->label('Berita unggulan'),
                TernaryFilter::make('is_breaking')
                    ->label('Breaking news'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->databaseTransaction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->databaseTransaction(),
                    BulkAction::make('publish')
                        ->label('Publikasikan')
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Publikasikan berita terpilih')
                        ->modalDescription('Berita yang dipilih akan ditayangkan sekarang. Berita yang sudah tayang tidak ada yang berubah.')
                        ->modalSubmitActionLabel('Publikasikan')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, BulkAction $action): void {
                            foreach ($records as $record) {
                                try {
                                    $isAlreadyLive = $record->status === 'published'
                                        && $record->published_at !== null
                                        && $record->published_at->isPast();

                                    if ($isAlreadyLive) {
                                        $action->reportBulkProcessingSuccessfulRecordsCount(1);

                                        continue;
                                    }

                                    $record->update(['status' => 'published', 'published_at' => now()]);
                                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                                } catch (\Throwable) {
                                    $action->reportBulkProcessingFailure();
                                }
                            }
                        }),
                    BulkAction::make('draft')
                        ->label('Jadikan Draf')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Jadikan draf')
                        ->modalDescription('Berita yang dipilih tidak lagi ditampilkan di portal publik. Tanggal publikasi tetap tersimpan.')
                        ->modalSubmitActionLabel('Jadikan Draf')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, BulkAction $action): void {
                            foreach ($records as $record) {
                                try {
                                    $record->update(['status' => 'draft']);
                                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                                } catch (\Throwable) {
                                    $action->reportBulkProcessingFailure();
                                }
                            }
                        }),
                    BulkAction::make('schedule')
                        ->label('Jadwalkan')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Jadwalkan publikasi')
                        ->modalDescription('Berita yang dipilih akan dipublikasikan pada waktu yang dipilih. Berita tidak tampil di portal sebelum waktunya.')
                        ->modalSubmitActionLabel('Jadwalkan')
                        ->deselectRecordsAfterCompletion()
                        ->schema([
                            DateTimePicker::make('published_at')
                                ->label('Tanggal & jam publikasi')
                                ->required()
                                ->minDate(now())
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data, BulkAction $action): void {
                            $publishedAt = CarbonImmutable::parse($data['published_at']);

                            foreach ($records as $record) {
                                try {
                                    $record->update(['status' => 'published', 'published_at' => $publishedAt]);
                                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                                } catch (\Throwable) {
                                    $action->reportBulkProcessingFailure();
                                }
                            }
                        }),
                    BulkAction::make('changeCategory')
                        ->label('Ubah Kategori')
                        ->icon(Heroicon::OutlinedTag)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Ubah kategori')
                        ->modalDescription('Kategori baru akan diterapkan ke semua berita yang dipilih.')
                        ->modalSubmitActionLabel('Ubah')
                        ->deselectRecordsAfterCompletion()
                        ->schema([
                            Select::make('category_id')
                                ->label('Kategori')
                                ->options(fn (): array => Category::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data, BulkAction $action): void {
                            $categoryId = (int) $data['category_id'];

                            foreach ($records as $record) {
                                try {
                                    $record->update(['category_id' => $categoryId]);
                                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                                } catch (\Throwable) {
                                    $action->reportBulkProcessingFailure();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}

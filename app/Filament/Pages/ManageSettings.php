<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AdminFilePreview;
use App\Support\AuditLogger;
use App\Support\MediaIntegrityGuard;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan Website';

    protected static ?string $title = 'Pengaturan Website';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.manage-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $this->form->fill(Setting::values());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('A. Identitas Website')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nama website')
                            ->required()
                            ->maxLength(150)
                            ->columnSpanFull(),
                        TextInput::make('site_short_name')
                            ->label('Nama singkat')
                            ->helperText('Ditampilkan pada logo header dan judul halaman.')
                            ->required()
                            ->maxLength(40),
                        TextInput::make('site_directorate')
                            ->label('Nama direktorat')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('site_ministry_line1')
                            ->label('Nama Direktorat Jenderal / Unit Eselon I')
                            ->helperText('Baris identitas instansi pada header. Kosongkan jika tidak ingin ditampilkan.')
                            ->maxLength(150)
                            ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state)),
                        TextInput::make('site_ministry_line2')
                            ->label('Nama Kementerian / Lembaga')
                            ->helperText('Baris identitas instansi pada header. Kosongkan jika tidak ingin ditampilkan.')
                            ->maxLength(150)
                            ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state)),
                        TextInput::make('site_portal_label')
                            ->label('Label portal')
                            ->helperText('Baris kedua pada identitas header, misalnya "Portal Informasi dan Buletin".')
                            ->maxLength(60),
                        Textarea::make('site_description')
                            ->label('Deskripsi website')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        Textarea::make('site_tagline')
                            ->label('Tagline')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        $this->mediaUpload('site_logo', 'Logo utama (header)', 64, 64, 2400, 2400),
                        $this->mediaUpload('site_logo_footer', 'Logo alternatif (footer)', 64, 64, 2400, 2400),
                        $this->mediaUpload('site_favicon', 'Favicon', 32, 32, 1024, 1024, square: true),
                    ]),
                Section::make('B. Informasi Kontak')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150),
                        TextInput::make('contact_phone')
                            ->label('Nomor telepon')
                            ->tel()
                            ->maxLength(50),
                        Textarea::make('contact_address')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        TextInput::make('site_parent_name')
                            ->label('Nama website induk')
                            ->helperText('Contoh: Kementerian Dalam Negeri. Kosongkan jika belum tersedia.')
                            ->maxLength(150),
                        TextInput::make('site_parent_url')
                            ->label('URL website induk')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_instagram')
                            ->label('Instagram')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_facebook')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_youtube')
                            ->label('YouTube')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_x')
                            ->label('X / Twitter')
                            ->url()
                            ->maxLength(255),
                    ]),
                Section::make('C. SEO dan Metadata')
                    ->description('Nilai ini menjadi fallback untuk halaman yang tidak memiliki judul atau deskripsi sendiri.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('Default SEO title')
                            ->helperText('Kosongkan untuk memakai nama website.')
                            ->maxLength(150)
                            ->columnSpanFull(),
                        Textarea::make('seo_description')
                            ->label('Default meta description')
                            ->rows(3)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        $this->mediaUpload('seo_og_image', 'Default Open Graph image', 600, 315, 4000, 4000, aspect: 'og'),
                        $this->mediaUpload('seo_social_image', 'Default social sharing image', 600, 315, 4000, 4000, aspect: 'og'),
                    ]),
                Section::make('D. Tampilan')
                    ->columns(2)
                    ->schema([
                        ColorPicker::make('theme_primary')
                            ->label('Warna utama')
                            ->default('#0b2836')
                            ->rule('regex:/^#[0-9a-fA-F]{6}$/')
                            ->required(),
                        ColorPicker::make('theme_secondary')
                            ->label('Warna sekunder')
                            ->default('#dda43a')
                            ->rule('regex:/^#[0-9a-fA-F]{6}$/')
                            ->required(),
                        TextInput::make('footer_copyright')
                            ->label('Copyright footer')
                            ->helperText('Gunakan {year} untuk tahun berjalan secara otomatis.')
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Toggle::make('show_header_topbar')
                            ->label('Tampilkan bar atas header'),
                        Toggle::make('show_header_search')
                            ->label('Tampilkan kolom pencarian'),
                        Toggle::make('show_footer_logo')
                            ->label('Tampilkan logo pada footer'),
                        Toggle::make('show_footer_contact')
                            ->label('Tampilkan informasi kontak pada footer'),
                        Toggle::make('show_footer_socials')
                            ->label('Tampilkan media sosial pada footer'),
                    ]),
                Section::make('E. Pengaturan Berita')
                    ->columns(2)
                    ->schema([
                        TextInput::make('homepage_news_count')
                            ->label('Jumlah berita di homepage')
                            ->helperText('Antara 3 sampai 24 berita.')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(24)
                            ->required(),
                        TextInput::make('posts_per_page')
                            ->label('Jumlah berita per halaman')
                            ->helperText('Berlaku untuk halaman berita dan rubrik. Antara 3 sampai 30.')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(30)
                            ->required(),
                        TextInput::make('popular_posts_count')
                            ->label('Jumlah berita populer')
                            ->helperText('Antara 1 sampai 10 berita.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->required(),
                        Toggle::make('breaking_news_enabled')
                            ->label('Tampilkan ticker "Terkini" di beranda')
                            ->helperText('Ticker hanya menampilkan berita terbit. Tandai berita sebagai "Breaking News" pada menu Berita agar tampil paling depan.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);

        $state = $this->data ?? [];
        $removals = MediaIntegrityGuard::requestedRemovals($state, Setting::mediaKeys());

        // Run before `getState()` so a blocked save never moves a queued upload
        // onto the public disk.
        MediaIntegrityGuard::assertPreserved(
            state: $state,
            persisted: array_intersect_key(Setting::rawValues(), array_flip(Setting::mediaKeys())),
            removals: $removals,
        );

        $values = $this->form->getState();

        foreach (Setting::mediaKeys() as $field) {
            if ($removals[$field] ?? false) {
                $values[$field] = null;
            }
        }

        Setting::putMany($values, function (array $before, array $after): void {
            if ($after === []) {
                return;
            }

            AuditLogger::record(
                event: 'settings_updated',
                module: 'settings',
                subjectLabel: 'Pengaturan Website',
                before: $before,
                after: $after,
            );
        });

        $this->form->fill(Setting::values());

        // Clear the one-shot removal flags so a later replacement is not
        // mistaken for a conflicting delete request.
        foreach (Setting::mediaKeys() as $field) {
            $this->data[MediaIntegrityGuard::removalFlag($field)] = false;
        }

        Notification::make()
            ->success()
            ->title('Pengaturan website disimpan')
            ->send();
    }

    private function mediaUpload(
        string $name,
        string $label,
        int $minWidth,
        int $minHeight,
        int $maxWidth,
        int $maxHeight,
        bool $square = false,
        ?string $aspect = null,
    ): Group {
        $dimensions = Rule::dimensions()
            ->minWidth($minWidth)
            ->minHeight($minHeight)
            ->maxWidth($maxWidth)
            ->maxHeight($maxHeight);

        if ($square) {
            $dimensions->minRatio(0.9)->maxRatio(1.1);
        }

        if ($aspect === 'og') {
            $dimensions->minRatio(1.7)->maxRatio(2.1);
        }

        return Group::make([
            FileUpload::make($name)
                ->label($label)
                ->image()
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->rules(['mimes:png,jpg,jpeg,webp'])
                ->maxSize(2048)
                ->disk('public')
                ->directory('settings')
                ->visibility('public')
                ->imagePreviewHeight('96')
                ->getUploadedFileUsing(AdminFilePreview::uploadedFileUsing())
                ->rule($dimensions)
                ->helperText('PNG, JPG, atau WebP. Maksimal 2 MB. SVG tidak diizinkan. Untuk mengganti gambar, klik Hapus pada gambar lama lalu unggah gambar baru.'),
            Toggle::make(MediaIntegrityGuard::removalFlag($name))
                ->label('Hapus media ini')
                ->helperText('Aktifkan hanya jika media ini memang ingin dihapus dari website. Opsi ini tidak diperlukan saat mengganti gambar.')
                ->visible(fn (): bool => filled(Setting::get($name)))
                ->dehydrated(false)
                ->default(false),
        ]);
    }
}

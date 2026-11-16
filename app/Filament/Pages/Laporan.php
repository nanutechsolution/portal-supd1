<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Support\AuditLogger;
use App\Support\ReportExporter;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Laporan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.laporan';

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
        $this->form->fill([
            'jenis' => 'berita',
            'status' => 'semua',
            'format' => 'xlsx',
            'category_id' => null,
            'dateFrom' => null,
            'dateUntil' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filter Laporan')
                ->description('Semua filter diterapkan secara konsisten ke seluruh jenis laporan.')
                ->columns(2)
                ->schema([
                    Select::make('jenis')
                        ->label('Jenis Laporan')
                        ->options(ReportExporter::TYPES)
                        ->required()
                        ->live()
                        ->native(false)
                        ->columnSpanFull(),
                    Select::make('status')
                        ->label('Status Berita')
                        ->options(ReportExporter::STATUSES)
                        ->required()
                        ->native(false),
                    Select::make('format')
                        ->label('Format File')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                        ])
                        ->required()
                        ->native(false),
                    Select::make('category_id')
                        ->label('Kategori')
                        ->options(fn (): array => Category::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->placeholder('Semua kategori'),
                    DatePicker::make('dateFrom')
                        ->label('Tanggal Mulai')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->placeholder('Semua waktu'),
                    DatePicker::make('dateUntil')
                        ->label('Tanggal Akhir')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->placeholder('Semua waktu'),
                ]),
            Section::make('Catatan metodologi')
                ->description('Kolom Views pada posts.views adalah total historis sejak berita dikelola. Data kunjungan per hari/bulan tersedia sejak rollup post_view_statistics diaktifkan; periode sebelum tanggal tersebut tidak bisa dilaporkan granular — gunakan kolom Total Views sebagai total historis, bukan estimasi.')
                ->schema([]),
        ])
            ->statePath('data');
    }

    public function export(): BinaryFileResponse
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);

        $state = $this->form->getState();

        AuditLogger::record(
            event: 'report_exported',
            module: 'laporan',
            subjectLabel: 'Ekspor laporan: '.(ReportExporter::TYPES[$state['jenis'] ?? 'berita'] ?? 'Laporan'),
            after: [
                'jenis' => $state['jenis'] ?? null,
                'format' => $state['format'] ?? null,
                'status' => $state['status'] ?? null,
                'category_id' => $state['category_id'] ?? null,
                'dateFrom' => $state['dateFrom'] ?? null,
                'dateUntil' => $state['dateUntil'] ?? null,
            ],
        );

        return ReportExporter::export($state);
    }
}

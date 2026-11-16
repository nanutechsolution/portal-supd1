<?php

namespace App\Filament\Widgets;

use App\Support\PortalAnalytics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?bool $isLazyLoaded = false;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Ringkasan';

    protected function getStats(): array
    {
        $period = PortalAnalytics::normalizePeriod($this->pageFilters['period'] ?? null);
        $snapshot = PortalAnalytics::snapshot($period);

        return [
            Stat::make('Berita Terbit', $this->formatNumber($snapshot['publishedInPeriod']))
                ->description('Pada periode terpilih')
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('success'),
            Stat::make('Total Kunjungan', $this->formatNumber(PortalAnalytics::viewsTotal()))
                ->description('Seluruh berita, mekanisme views')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->icon(Heroicon::OutlinedEye)
                ->color('primary')
                ->chart($snapshot['visits']['data']),
            Stat::make('Total Berita Terbit', $this->formatNumber(PortalAnalytics::publishedTotal()))
                ->description('Keseluruhan')
                ->icon(Heroicon::OutlinedNewspaper)
                ->color('info'),
            Stat::make('Draf', $this->formatNumber(PortalAnalytics::draftCount()))
                ->description('Belum diterbitkan')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('gray'),
            Stat::make('Terjadwal', $this->formatNumber(PortalAnalytics::scheduledCount()))
                ->description('Menunggu publikasi')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make('Kategori Aktif', $this->formatNumber(PortalAnalytics::activeCategories()))
                ->description('Rubrik yang aktif')
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->color('success'),
        ];
    }

    private function formatNumber(int $number): string
    {
        return number_format($number, 0, ',', '.');
    }
}

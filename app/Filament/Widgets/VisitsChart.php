<?php

namespace App\Filament\Widgets;

use App\Support\PortalAnalytics;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class VisitsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?bool $isLazyLoaded = false;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Kunjungan per Periode';

    protected ?string $description = 'Jumlah pembukaan berita pada periode terpilih.';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $period = PortalAnalytics::normalizePeriod($this->pageFilters['period'] ?? null);
        $buckets = PortalAnalytics::snapshot($period)['visits'];

        return [
            'labels' => $buckets['labels'],
            'datasets' => [
                [
                    'label' => 'Kunjungan',
                    'data' => $buckets['data'],
                    'borderColor' => '#0b2836',
                    'backgroundColor' => 'rgba(11, 40, 54, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }
}

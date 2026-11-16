<?php

namespace App\Filament\Widgets;

use App\Support\PortalAnalytics;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PublicationChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?bool $isLazyLoaded = false;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Publikasi per Periode';

    protected ?string $description = 'Jumlah berita dipublikasikan pada periode terpilih.';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $period = PortalAnalytics::normalizePeriod($this->pageFilters['period'] ?? null);
        $buckets = PortalAnalytics::snapshot($period)['publication'];

        return [
            'labels' => $buckets['labels'],
            'datasets' => [
                [
                    'label' => 'Berita dipublikasikan',
                    'data' => $buckets['data'],
                    'backgroundColor' => '#dda43a',
                    'borderColor' => '#c9891f',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
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

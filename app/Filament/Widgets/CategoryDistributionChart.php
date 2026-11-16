<?php

namespace App\Filament\Widgets;

use App\Support\PortalAnalytics;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CategoryDistributionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?bool $isLazyLoaded = false;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Distribusi per Kategori';

    protected ?string $description = 'Berita yang dipublikasikan per rubrik pada periode terpilih.';

    protected int|string|array $columnSpan = 1;

    /**
     * @var list<string>
     */
    private const PALETTE = ['#0b2836', '#dda43a', '#46818f', '#c9891f', '#356874', '#e9be64', '#264650', '#6a9fae'];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $period = PortalAnalytics::normalizePeriod($this->pageFilters['period'] ?? null);
        $distribution = PortalAnalytics::snapshot($period)['categories'];

        return [
            'labels' => $distribution['labels'],
            'datasets' => [
                [
                    'label' => 'Berita',
                    'data' => $distribution['data'],
                    'backgroundColor' => array_slice(self::PALETTE, 0, max(count($distribution['data']), 1)),
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }

    public function isEmpty(): bool
    {
        $distribution = PortalAnalytics::snapshot(PortalAnalytics::normalizePeriod($this->pageFilters['period'] ?? null))['categories'];

        return $distribution['data'] === [];
    }
}

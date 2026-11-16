<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AnalyticsOverview;
use App\Filament\Widgets\CategoryDistributionChart;
use App\Filament\Widgets\LatestPosts;
use App\Filament\Widgets\PublicationChart;
use App\Filament\Widgets\TopPosts;
use App\Filament\Widgets\VisitsChart;
use App\Models\User;
use App\Support\PortalAnalytics;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Analitik extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/analitik';

    protected static ?string $navigationLabel = 'Analitik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Analitik';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function getWidgets(): array
    {
        return [
            AnalyticsOverview::class,
            PublicationChart::class,
            VisitsChart::class,
            CategoryDistributionChart::class,
            TopPosts::class,
            LatestPosts::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Periode')
                ->options(PortalAnalytics::periodOptions())
                ->default(PortalAnalytics::PERIOD_30)
                ->required()
                ->live()
                ->native(false),
        ]);
    }
}

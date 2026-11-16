<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Bulletin;
use App\Models\GalleryItem;
use App\Models\Post;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PortalStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Berita', (string) Post::query()->count())
                ->description('Seluruh berita di panel')
                ->icon(Heroicon::OutlinedNewspaper)
                ->color('primary'),
            Stat::make('Berita Terbit', (string) Post::query()->where('status', PostStatus::Published)->count())
                ->description('Tampil di website publik')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('success'),
            Stat::make('Draf', (string) Post::query()->where('status', PostStatus::Draft)->count())
                ->description('Belum diterbitkan')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('gray'),
            Stat::make('Total Buletin', (string) Bulletin::query()->count())
                ->description('Seluruh edisi buletin')
                ->icon(Heroicon::OutlinedBookOpen)
                ->color('info'),
            Stat::make('Foto Galeri', (string) GalleryItem::query()->count())
                ->description('Dokumentasi terunggah')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('warning'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopPosts extends TableWidget
{
    protected static ?bool $isLazyLoaded = false;

    protected static ?string $heading = 'Berita Paling Banyak Dibaca';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Post::query()
                ->published()
                ->with(['category', 'author'])
                ->orderByDesc('views')
                ->orderByDesc('published_at')
                ->limit(5))
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->toggleable(),
                TextColumn::make('views')
                    ->label('Kunjungan')
                    ->badge()
                    ->color('primary')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Post $record): string => PostResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}

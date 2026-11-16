<?php

namespace App\Models;

use App\Support\MediaFiles;
use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'image', 'description', 'documented_at'])]
class GalleryItem extends Model
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'documented_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (GalleryItem $item): void {
            if (! $item->wasChanged('image')) {
                return;
            }

            $oldPath = $item->getOriginal('image');

            if (is_string($oldPath) && $oldPath !== $item->image) {
                MediaFiles::deleteFromPublicDisk($oldPath, 'gallery');
            }
        });

        static::deleted(function (GalleryItem $item): void {
            MediaFiles::deleteFromPublicDisk($item->image, 'gallery');
        });
    }

    /**
     * @param  Builder<GalleryItem>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByRaw('COALESCE(documented_at, created_at) DESC');
    }

    public function imageUrl(): string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : asset('images/placeholder-news.svg');
    }
}

<?php

namespace App\Models;

use App\Support\MediaFiles;
use Database\Factories\BulletinFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title',
    'edition_number',
    'year',
    'description',
    'cover',
    'file_path',
    'published_at',
    'is_published',
])]
class Bulletin extends Model
{
    /** @use HasFactory<BulletinFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Bulletin $bulletin): void {
            if ($bulletin->is_published && blank($bulletin->published_at)) {
                $bulletin->published_at = now();
            }
        });

        static::updated(function (Bulletin $bulletin): void {
            if ($bulletin->wasChanged('cover')) {
                $oldCover = $bulletin->getOriginal('cover');

                if (is_string($oldCover) && $oldCover !== $bulletin->cover) {
                    MediaFiles::deleteFromPublicDisk($oldCover, 'bulletins/covers');
                }
            }

            if ($bulletin->wasChanged('file_path')) {
                $oldFile = $bulletin->getOriginal('file_path');

                if (is_string($oldFile) && $oldFile !== $bulletin->file_path) {
                    MediaFiles::deleteFromPublicDisk($oldFile, 'bulletins/pdf');
                }
            }
        });

        static::deleted(function (Bulletin $bulletin): void {
            MediaFiles::deleteFromPublicDisk($bulletin->cover, 'bulletins/covers');
            MediaFiles::deleteFromPublicDisk($bulletin->file_path, 'bulletins/pdf');
        });
    }

    /**
     * @param  Builder<Bulletin>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->is_published
            && (! $this->published_at || $this->published_at->isPast());
    }

    public function coverUrl(): string
    {
        return $this->cover
            ? Storage::disk('public')->url($this->cover)
            : asset('images/placeholder-bulletin.svg');
    }

    public function hasFile(): bool
    {
        return filled($this->file_path) && Storage::disk('public')->exists($this->file_path);
    }
}

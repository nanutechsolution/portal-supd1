<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Support\MediaFiles;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'category_id',
    'user_id',
    'title',
    'slug',
    'excerpt',
    'content',
    'featured_image',
    'is_featured',
    'is_breaking',
    'status',
    'published_at',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if (blank($post->slug)) {
                $post->slug = static::uniqueSlug($post->title, $post->id);
            }

            if ($post->status === PostStatus::Published && blank($post->published_at)) {
                $post->published_at = now();
            }
        });

        static::updated(function (Post $post): void {
            if (! $post->wasChanged('featured_image')) {
                return;
            }

            $oldPath = $post->getOriginal('featured_image');

            if (is_string($oldPath) && $oldPath !== $post->featured_image) {
                MediaFiles::deleteFromPublicDisk($oldPath, 'posts');
            }
        });

        static::deleted(function (Post $post): void {
            MediaFiles::deleteFromPublicDisk($post->featured_image, 'posts');
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'berita';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopeBreaking(Builder $query): void
    {
        $query->where('is_breaking', true);
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopeLatestPublished(Builder $query): void
    {
        $query->orderByRaw('COALESCE(published_at, created_at) DESC');
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && (! $this->published_at || $this->published_at->isPast());
    }

    public function readingTime(): int
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($words / 200));
    }

    public function featuredImageUrl(): string
    {
        return $this->featured_image
            ? Storage::disk('public')->url($this->featured_image)
            : asset('images/placeholder-news.svg');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

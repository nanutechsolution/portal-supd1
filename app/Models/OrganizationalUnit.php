<?php

namespace App\Models;

use App\Enums\OrganizationalLevel;
use App\Support\MediaFiles;
use Database\Factories\OrganizationalUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A position or work unit inside the directorate hierarchy.
 *
 * The parent relation models the reporting line; a unit may only have one
 * direct superior. Hierarchy cycles are rejected by the admin form and by
 * `ParentUnitIsNotDescendant`.
 */
#[Fillable([
    'name',
    'official_name',
    'level',
    'photo',
    'parent_id',
    'sort_order',
    'description',
    'is_active',
    'is_published',
])]
class OrganizationalUnit extends Model
{
    /** @use HasFactory<OrganizationalUnitFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => OrganizationalLevel::class,
            'is_active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (OrganizationalUnit $unit): void {
            if (! $unit->wasChanged('photo')) {
                return;
            }

            $oldPath = $unit->getOriginal('photo');

            if (is_string($oldPath) && $oldPath !== $unit->photo) {
                MediaFiles::deleteFromPublicDisk($oldPath, 'structure');
            }
        });

        static::deleted(function (OrganizationalUnit $unit): void {
            MediaFiles::deleteFromPublicDisk($unit->photo, 'structure');
        });
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<OrganizationalUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * @return HasMany<OrganizationalUnit, $this>
     */
    public function visibleChildren(): HasMany
    {
        return $this->children()->visible();
    }

    /**
     * @param  Builder<OrganizationalUnit>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<OrganizationalUnit>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<OrganizationalUnit>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->active()->published();
    }

    /**
     * @param  Builder<OrganizationalUnit>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Published hierarchy, built defensively so a corrupt parent chain can
     * never recurse forever.
     *
     * @return Collection<int, OrganizationalUnit>
     */
    public static function publicTree(int $maxDepth = 10): Collection
    {
        $units = static::query()
            ->visible()
            ->ordered()
            ->get();

        /** @var Collection<int, Collection<int, OrganizationalUnit>> $grouped */
        $grouped = $units->groupBy(fn (OrganizationalUnit $unit): int => (int) ($unit->parent_id ?? 0));

        /** @var array<int, true> $seen */
        $seen = [];

        $build = function (int $parentId, int $depth) use (&$build, &$seen, $grouped, $maxDepth): Collection {
            if ($depth > $maxDepth) {
                return new Collection;
            }

            return $grouped
                ->get($parentId, new Collection)
                ->reject(fn (OrganizationalUnit $unit): bool => isset($seen[$unit->getKey()]))
                ->map(function (OrganizationalUnit $unit) use (&$build, &$seen, $depth): OrganizationalUnit {
                    $seen[$unit->getKey()] = true;
                    $unit->setRelation('visibleChildren', $build((int) $unit->getKey(), $depth + 1));

                    return $unit;
                })
                ->values();
        };

        return $build(0, 1);
    }

    /**
     * Identifier of every unit below this one.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        /** @var Collection<int, OrganizationalUnit> $units */
        $units = static::query()->get(['id', 'parent_id']);
        $children = $units->groupBy(fn (OrganizationalUnit $unit): int => (int) ($unit->parent_id ?? 0));

        $ids = [];
        $stack = [$this->getKey()];

        while ($stack !== []) {
            $current = array_pop($stack);

            foreach ($children->get($current, new Collection) as $child) {
                if ($child->getKey() === $this->getKey() || in_array($child->getKey(), $ids, true)) {
                    continue;
                }

                $ids[] = $child->getKey();
                $stack[] = $child->getKey();
            }
        }

        return $ids;
    }

    public function photoUrl(): ?string
    {
        if (blank($this->photo)) {
            return null;
        }

        return Storage::disk('public')->url($this->photo);
    }

    public function cardPhotoUrl(): ?string
    {
        if ($this->resolvedLevel()?->hasPhoto() === false) {
            return null;
        }

        return $this->photoUrl();
    }

    /**
     * Level yang dipakai bagan. Kolom `level` tetap menjadi sumber utama;
     * nama jabatan hanya menjadi cadangan untuk data lama yang belum terisi.
     */
    public function resolvedLevel(): ?OrganizationalLevel
    {
        $level = $this->getAttribute('level');

        if ($level instanceof OrganizationalLevel) {
            return $level;
        }

        $name = Str::lower(trim((string) $this->name));

        return match (true) {
            str_contains($name, 'fungsional') => OrganizationalLevel::Fungsional,
            str_contains($name, 'katim'), str_contains($name, 'ketua tim') => OrganizationalLevel::Katim,
            str_contains($name, 'kapokja'), str_contains($name, 'kelompok kerja') => OrganizationalLevel::Kapokja,
            str_contains($name, 'subbagian'), str_contains($name, 'sub bagian') => OrganizationalLevel::Kasubbag,
            str_contains($name, 'kepala bagian'),
            str_contains($name, 'subdirektorat'),
            str_contains($name, 'substansi') => OrganizationalLevel::KepalaBagian,
            str_contains($name, 'direktur') => OrganizationalLevel::Pimpinan,
            $this->parent_id === null => OrganizationalLevel::Pimpinan,
            default => null,
        };
    }

    public function hasPosition(): bool
    {
        return filled($this->name);
    }

    public function positionTitle(): string
    {
        return trim((string) $this->name);
    }

    public function personName(): ?string
    {
        $name = trim((string) $this->official_name);

        return $name !== '' ? $name : null;
    }

    /**
     * Inisial untuk kartu tanpa foto. Nama pejabat dipakai lebih dulu,
     * kemudian nama jabatan sebagai cadangan.
     */
    public function initials(int $limit = 2): string
    {
        $source = $this->personName() ?? $this->positionTitle();

        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $source) ?? '';
        $words = preg_split('/\s+/u', trim($normalized)) ?: [];
        $ignored = ['dr', 'drs', 'dra', 'ir', 'prof', 'h', 'hj'];

        $words = array_values(array_filter(
            $words,
            fn (string $word): bool => ! in_array(mb_strtolower($word), $ignored, true),
        ));

        if ($words === []) {
            return '';
        }

        $selected = array_slice($words, 0, max(1, $limit));

        return implode('', array_map(
            fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)),
            $selected,
        ));
    }
}

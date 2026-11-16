<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Validated, immutable appearance preferences for an admin panel user.
 *
 * The class is the single source of truth for the values that may be stored
 * in `users.appearance`. Unknown or corrupted values always fall back to the
 * documented defaults so a bad row can never inject CSS or JavaScript.
 */
final class Appearance
{
    /** @var list<string> */
    public const MODES = ['light', 'dark', 'system'];

    /** @var list<string> */
    public const ACCENTS = ['blue', 'indigo', 'green', 'purple', 'orange'];

    /** @var list<string> */
    public const DENSITIES = ['comfortable', 'compact'];

    /** @var list<string> */
    public const SIDEBARS = ['expanded', 'collapsed'];

    /** @var list<string> */
    public const SIDEBAR_COLORS = ['default', 'slate', 'teal', 'blue', 'indigo', 'green', 'purple', 'orange'];

    private function __construct(
        public readonly string $mode,
        public readonly string $accent,
        public readonly string $density,
        public readonly string $sidebar,
        public readonly string $sidebarColor,
    ) {}

    public static function defaults(): self
    {
        return new self(
            mode: 'system',
            accent: 'blue',
            density: 'comfortable',
            sidebar: 'expanded',
            sidebarColor: 'default',
        );
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    public static function from(?array $state): self
    {
        $defaults = self::defaults();

        return new self(
            mode: self::allowed($state['mode'] ?? null, self::MODES, $defaults->mode),
            accent: self::allowed($state['accent'] ?? null, self::ACCENTS, $defaults->accent),
            density: self::allowed($state['density'] ?? null, self::DENSITIES, $defaults->density),
            sidebar: self::allowed($state['sidebar'] ?? null, self::SIDEBARS, $defaults->sidebar),
            sidebarColor: self::allowed($state['sidebarColor'] ?? null, self::SIDEBAR_COLORS, $defaults->sidebarColor),
        );
    }

    /**
     * @return array{mode: string, accent: string, density: string, sidebar: string, sidebarColor: string}
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'accent' => $this->accent,
            'density' => $this->density,
            'sidebar' => $this->sidebar,
            'sidebarColor' => $this->sidebarColor,
        ];
    }

    public function isCompact(): bool
    {
        return $this->density === 'compact';
    }

    public function isSidebarExpanded(): bool
    {
        return $this->sidebar === 'expanded';
    }

    /**
     * Resolve the Filament palette for an accent. Only the whitelisted keys are
     * ever passed to `Color`, so user input can never reach a CSS value.
     *
     * @return array<int, string>
     */
    public static function palette(string $accent): array
    {
        return match ($accent) {
            'indigo' => Color::Indigo,
            'green' => Color::Green,
            'purple' => Color::Purple,
            'orange' => Color::Orange,
            default => Color::hex('#0b4f63'),
        };
    }

    /**
     * @return array<string, array{label: string, swatches: list<string>}>
     */
    public static function accentOptions(): array
    {
        $options = [];

        foreach (self::ACCENTS as $accent) {
            $palette = self::palette($accent);

            $options[$accent] = [
                'label' => self::accentLabel($accent),
                'swatches' => [$palette[200], $palette[500], $palette[800]],
            ];
        }

        return $options;
    }

    public static function accentLabel(string $accent): string
    {
        return match ($accent) {
            'indigo' => 'Indigo',
            'green' => 'Green',
            'purple' => 'Purple',
            'orange' => 'Orange',
            default => 'Blue',
        };
    }

    /**
     * The independent sidebar tint palette, or `null` when the sidebar keeps
     * Filament's default surface.
     *
     * @return array<int, string>|null
     */
    public static function paletteForSidebar(string $sidebarColor): ?array
    {
        return match ($sidebarColor) {
            'slate' => Color::Slate,
            'teal' => Color::Teal,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'green' => Color::Green,
            'purple' => Color::Purple,
            'orange' => Color::Orange,
            default => null,
        };
    }

    /**
     * @return array<int, string>|null
     */
    public function sidebarPalette(): ?array
    {
        return self::paletteForSidebar($this->sidebarColor);
    }

    /**
     * @return array<string, array{label: string, swatches: list<string>}>
     */
    public static function sidebarColorOptions(): array
    {
        $options = [];

        foreach (self::SIDEBAR_COLORS as $sidebarColor) {
            $palette = self::paletteForSidebar($sidebarColor);

            $options[$sidebarColor] = [
                'label' => self::sidebarColorLabel($sidebarColor),
                'swatches' => $palette
                    ? [$palette[100], $palette[500], $palette[900]]
                    : ['#ffffff', '#e4e4e7', '#18181b'],
            ];
        }

        return $options;
    }

    public static function sidebarColorLabel(string $sidebarColor): string
    {
        return match ($sidebarColor) {
            'slate' => 'Slate',
            'teal' => 'Teal',
            'blue' => 'Blue',
            'indigo' => 'Indigo',
            'green' => 'Green',
            'purple' => 'Purple',
            'orange' => 'Orange',
            default => 'Bawaan',
        };
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function allowed(mixed $value, array $allowed, string $fallback): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
    }
}

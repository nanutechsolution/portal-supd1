<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\Appearance;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use UnitEnum;

class AppearanceSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Tampilan';

    protected static ?string $title = 'Tampilan';

    protected static ?string $slug = 'tampilan';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.appearance-settings';

    public string $mode = 'system';

    public string $accent = 'blue';

    public string $density = 'comfortable';

    public string $sidebar = 'expanded';

    public string $sidebarColor = 'default';

    /**
     * @var array<string, array{label: string, swatches: list<string>}>
     */
    public array $accentOptions = [];

    /**
     * @var array<string, array{label: string, swatches: list<string>}>
     */
    public array $sidebarColorOptions = [];

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $appearance = Appearance::from($user->appearance);

        $this->mode = $appearance->mode;
        $this->accent = $appearance->accent;
        $this->density = $appearance->density;
        $this->sidebar = $appearance->sidebar;
        $this->sidebarColor = $appearance->sidebarColor;
        $this->accentOptions = Appearance::accentOptions();
        $this->sidebarColorOptions = Appearance::sidebarColorOptions();
    }

    public function save(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $validated = $this->validate([
            'mode' => ['required', Rule::in(Appearance::MODES)],
            'accent' => ['required', Rule::in(Appearance::ACCENTS)],
            'density' => ['required', Rule::in(Appearance::DENSITIES)],
            'sidebar' => ['required', Rule::in(Appearance::SIDEBARS)],
            'sidebarColor' => ['required', Rule::in(Appearance::SIDEBAR_COLORS)],
        ]);

        $user->appearance = Appearance::from($validated)->toArray();
        $user->save();

        // The primary palette is rendered server-side, so a full reload is
        // required for the new accent to replace the current CSS variables.
        session()->flash('appearance_saved', true);

        $this->redirect(static::getUrl(), navigate: false);
    }
}

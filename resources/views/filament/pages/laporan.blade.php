<x-filament-panels::page>
    <form wire:submit="export" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray">
            Unduh Laporan
        </x-filament::button>
    </form>
</x-filament-panels::page>

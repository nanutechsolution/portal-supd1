<x-filament-panels::page>
    @php
        $modeOptions = [
            'light' => ['label' => 'Terang', 'description' => 'Selalu memakai latar terang.'],
            'dark' => ['label' => 'Gelap', 'description' => 'Selalu memakai latar gelap.'],
            'system' => ['label' => 'Sistem', 'description' => 'Mengikuti pengaturan perangkat Anda.'],
        ];

        $densityOptions = [
            'comfortable' => ['label' => 'Nyaman', 'description' => 'Jarak standar untuk pemakaian sehari-hari.'],
            'compact' => ['label' => 'Ringkas', 'description' => 'Baris dan kontrol lebih rapat untuk data padat.'],
        ];

        $sidebarOptions = [
            'expanded' => ['label' => 'Terbuka', 'description' => 'Menu samping selalu menampilkan label.'],
            'collapsed' => ['label' => 'Terlipat', 'description' => 'Menu samping diringkas menjadi ikon.'],
        ];
    @endphp

    <div class="appearance-settings">
        <p class="appearance-intro">
            Preferensi ini hanya berlaku untuk akun <strong>{{ auth()->user()?->name }}</strong> dan tersimpan setelah
            Anda menekan <strong>Simpan preferensi</strong>.
        </p>

        @if (session('appearance_saved'))
            <div class="appearance-status" role="status">
                Preferensi tampilan berhasil disimpan.
            </div>
        @endif

        <form wire:submit="save" class="appearance-form">
            <x-filament::section
                heading="Mode tampilan"
                description="Pilih tampilan terang, gelap, atau ikuti pengaturan sistem operasi."
            >
                <fieldset class="appearance-fieldset">
                    <legend class="appearance-legend">Mode tampilan</legend>

                    <div class="appearance-options appearance-options-modes">
                        @foreach ($modeOptions as $value => $option)
                            <label class="appearance-card">
                                <input
                                    type="radio"
                                    name="mode"
                                    value="{{ $value }}"
                                    wire:model="mode"
                                    @checked($mode === $value)
                                >

                                <span class="appearance-mock appearance-mock-{{ $value }}" aria-hidden="true">
                                    <span class="appearance-mock-sidebar"></span>
                                    <span class="appearance-mock-content">
                                        <span class="appearance-mock-bar"></span>
                                        <span class="appearance-mock-line"></span>
                                        <span class="appearance-mock-line appearance-mock-line-short"></span>
                                    </span>
                                </span>

                                <span class="appearance-card-label">
                                    <span>{{ $option['label'] }}</span>
                                    <span class="appearance-card-check" aria-hidden="true"></span>
                                </span>
                                <span class="appearance-card-description">{{ $option['description'] }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('mode')
                        <p class="appearance-error">{{ $message }}</p>
                    @enderror
                </fieldset>
            </x-filament::section>

            <x-filament::section
                heading="Warna aksen"
                description="Warna aksen dipakai untuk tombol utama, tautan aktif, dan sorotan antarmuka."
            >
                <fieldset class="appearance-fieldset">
                    <legend class="appearance-legend">Warna aksen</legend>

                    <div class="appearance-options appearance-options-accents">
                        @foreach ($accentOptions as $value => $option)
                            <label class="appearance-card appearance-card-accent">
                                <input
                                    type="radio"
                                    name="accent"
                                    value="{{ $value }}"
                                    wire:model="accent"
                                    @checked($accent === $value)
                                >

                                <span class="appearance-swatches" aria-hidden="true">
                                    @foreach ($option['swatches'] as $swatch)
                                        <span class="appearance-swatch" style="background-color: {{ $swatch }}"></span>
                                    @endforeach
                                </span>

                                <span class="appearance-card-label">
                                    <span>{{ $option['label'] }}</span>
                                    <span class="appearance-card-check" aria-hidden="true"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @error('accent')
                        <p class="appearance-error">{{ $message }}</p>
                    @enderror
                </fieldset>
            </x-filament::section>

            <x-filament::section
                heading="Warna sidebar"
                description="Warna latar menu samping. Pengaturan ini terpisah dari warna aksen."
            >
                <fieldset class="appearance-fieldset">
                    <legend class="appearance-legend">Warna sidebar</legend>

                    <div class="appearance-options appearance-options-sidebar-colors">
                        @foreach ($sidebarColorOptions as $value => $option)
                            <label class="appearance-card appearance-card-sidebar">
                                <input
                                    type="radio"
                                    name="sidebarColor"
                                    value="{{ $value }}"
                                    wire:model="sidebarColor"
                                    @checked($sidebarColor === $value)
                                >

                                <span class="appearance-swatches" aria-hidden="true">
                                    @foreach ($option['swatches'] as $swatch)
                                        <span class="appearance-swatch" style="background-color: {{ $swatch }}"></span>
                                    @endforeach
                                </span>

                                <span class="appearance-card-label">
                                    <span>{{ $option['label'] }}</span>
                                    <span class="appearance-card-check" aria-hidden="true"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <p class="appearance-hint">
                        Pilihan “Bawaan” mempertahankan warna permukaan sidebar Filament saat ini.
                    </p>

                    @error('sidebarColor')
                        <p class="appearance-error">{{ $message }}</p>
                    @enderror
                </fieldset>
            </x-filament::section>

            <div class="appearance-split">
                <x-filament::section
                    heading="Kepadatan"
                    description="Atur seberapa rapat tabel, formulir, tombol, dan widget ditampilkan."
                >
                    <fieldset class="appearance-fieldset">
                        <legend class="appearance-legend">Kepadatan</legend>

                        <div class="appearance-options appearance-options-compact">
                            @foreach ($densityOptions as $value => $option)
                                <label class="appearance-card">
                                    <input
                                        type="radio"
                                        name="density"
                                        value="{{ $value }}"
                                        wire:model="density"
                                        @checked($density === $value)
                                    >

                                    <span class="appearance-density appearance-density-{{ $value }}" aria-hidden="true">
                                        <span class="appearance-density-row"></span>
                                        <span class="appearance-density-row"></span>
                                        <span class="appearance-density-row"></span>
                                    </span>

                                    <span class="appearance-card-label">
                                        <span>{{ $option['label'] }}</span>
                                        <span class="appearance-card-check" aria-hidden="true"></span>
                                    </span>
                                    <span class="appearance-card-description">{{ $option['description'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('density')
                            <p class="appearance-error">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </x-filament::section>

                <x-filament::section
                    heading="Sidebar"
                    description="Tentukan apakah menu samping terbuka atau terlipat saat panel dimuat."
                >
                    <fieldset class="appearance-fieldset">
                        <legend class="appearance-legend">Sidebar</legend>

                        <div class="appearance-options appearance-options-sidebar">
                            @foreach ($sidebarOptions as $value => $option)
                                <label class="appearance-card">
                                    <input
                                        type="radio"
                                        name="sidebar"
                                        value="{{ $value }}"
                                        wire:model="sidebar"
                                        @checked($sidebar === $value)
                                    >

                                    <span class="appearance-sidebar appearance-sidebar-{{ $value }}" aria-hidden="true">
                                        <span class="appearance-sidebar-panel"></span>
                                        <span class="appearance-sidebar-content">
                                            <span class="appearance-mock-bar"></span>
                                            <span class="appearance-mock-line"></span>
                                        </span>
                                    </span>

                                    <span class="appearance-card-label">
                                        <span>{{ $option['label'] }}</span>
                                        <span class="appearance-card-check" aria-hidden="true"></span>
                                    </span>
                                    <span class="appearance-card-description">{{ $option['description'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('sidebar')
                            <p class="appearance-error">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </x-filament::section>
            </div>

            <div class="appearance-actions">
                <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled">
                    Simpan preferensi
                </x-filament::button>

                <span class="appearance-hint" wire:loading>
                    Menyimpan…
                </span>
            </div>
        </form>
    </div>
</x-filament-panels::page>

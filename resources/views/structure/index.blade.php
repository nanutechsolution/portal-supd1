<x-layouts.public
    title="Struktur Organisasi"
    :description="'Susunan jabatan dan unit kerja di lingkungan '.$site['site_directorate'].'.'"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow text-accent-300">Organisasi</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">Struktur Organisasi</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-300">
                Susunan jabatan dan unit kerja di lingkungan {{ $site['site_directorate'] }}.
                Bagan berikut hanya menampilkan data yang sudah dikonfirmasi dan dipublikasikan.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($units->isNotEmpty())
            <div class="space-y-5">
                @foreach ($units as $unit)
                    <x-public.structure-node :unit="$unit" :depth="1" />
                @endforeach
            </div>

            <p class="mt-8 text-xs leading-relaxed text-ink-500">
                Informasi nama pejabat dan hubungan atasan-bawahan ditampilkan sesuai data yang telah diperbarui administrator.
            </p>
        @else
            <x-public.empty-state
                title="Struktur organisasi belum tersedia"
                message="Data jabatan dan unit kerja belum dipublikasikan. Silakan kembali lagi setelah administrator melengkapi struktur organisasi."
            />
        @endif
    </div>
</x-layouts.public>

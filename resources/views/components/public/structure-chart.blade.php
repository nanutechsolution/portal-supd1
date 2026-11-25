@props([
    'units',
    'institution' => '',
    'directorate' => '',
    'subtitle' => '',
    'watermark' => '',
])

<section class="org-chart" aria-labelledby="org-chart-heading">
    @if ($watermark !== '')
        <span class="org-chart__watermark" aria-hidden="true">{{ $watermark }}</span>
    @endif

    <div class="org-chart__inner">
        <header class="org-chart__header">
            <h1 id="org-chart-heading" class="sr-only">Struktur Organisasi</h1>

            <div class="org-chart__identity">
                @if ($institution !== '')
                    <p class="org-chart__institution">{{ $institution }}</p>
                @endif

                @if ($directorate !== '')
                    <p class="org-chart__title">{{ $directorate }}</p>
                @endif

                @if ($subtitle !== '')
                    <p class="org-chart__subtitle">{{ $subtitle }}</p>
                @endif
            </div>
        </header>

        @if ($units->isNotEmpty())
            <div class="org-chart__scroll"
                 role="region"
                 aria-label="Bagan struktur organisasi. Geser ke samping untuk melihat seluruh bagan."
                 tabindex="0">
                <div class="org-chart__canvas">
                    <ul class="org-tree">
                        @foreach ($units as $unit)
                            <x-public.structure-branch :unit="$unit" :depth="1" />
                        @endforeach
                    </ul>
                </div>
            </div>

            <p class="org-chart__note">
                Informasi nama pejabat dan hubungan atasan-bawahan ditampilkan sesuai data yang telah diperbarui administrator.
            </p>
        @else
            <div class="org-chart__empty">
                <p class="org-chart__empty-title">Struktur organisasi belum tersedia</p>
                <p class="org-chart__empty-message">
                    Data jabatan dan unit kerja belum dipublikasikan. Silakan kembali lagi setelah administrator melengkapi struktur organisasi.
                </p>
            </div>
        @endif
    </div>
</section>

@php
    $institution = trim((string) ($site['site_ministry_line2'] ?? ''));
    $directorate = trim((string) ($site['site_directorate'] ?? ''));
    $subtitle = trim((string) ($site['site_ministry_line1'] ?? ''));
    $watermark = trim((string) ($site['site_short_name'] ?? ''));
@endphp

<x-layouts.public
    title="Struktur Organisasi"
    :description="'Susunan jabatan dan unit kerja di lingkungan '.$directorate.'.'"
>
    <x-public.structure-chart
        :units="$units"
        :institution="$institution"
        :directorate="$directorate"
        :subtitle="$subtitle"
        :watermark="$watermark"
    />
</x-layouts.public>

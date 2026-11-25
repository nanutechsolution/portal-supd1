@props(['unit'])

@php
    $level = $unit->resolvedLevel();
    $variant = $level?->cardVariant() ?? 'default';
    $hasPhoto = $level?->hasPhoto() ?? true;
    $photoUrl = $hasPhoto ? $unit->photoUrl() : null;
    $personName = $unit->personName();
    $initials = $unit->initials();
@endphp

<article @class([
    'org-card',
    'org-card--'.$variant,
])>
    <div class="org-card__content">
        <p class="org-card__position">{{ $unit->positionTitle() }}</p>

        @if ($personName !== null)
            <p class="org-card__person">{{ $personName }}</p>
        @endif
    </div>

    @if ($hasPhoto)
        <div class="org-card__media">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}"
                     alt="Foto {{ $personName ?? $unit->positionTitle() }}"
                     loading="lazy"
                     decoding="async">
            @else
                <span class="org-card__fallback" aria-hidden="true">{{ $initials }}</span>
            @endif
        </div>
    @endif
</article>

@props(['unit', 'depth' => 1])

@php
    $children = $unit->visibleChildren ?? collect();
    $allTeam = $children->isNotEmpty() && $children->every(
        fn ($child): bool => $child->resolvedLevel() === \App\Enums\OrganizationalLevel::Katim,
    );
@endphp

@if (! $unit->hasPosition())
    @foreach ($children as $child)
        <x-public.structure-branch :unit="$child" :depth="$depth + 1" />
    @endforeach
@else
    <li {{ $attributes->merge(['class' => 'org-node']) }}>
        <div @class(['org-card-slot', 'org-card-slot--connected' => $depth > 1])>
            <x-public.structure-card :unit="$unit" />
        </div>

        @if ($children->isNotEmpty())
            @if ($allTeam)
                <x-public.structure-team-list :units="$children" />
            @else
                <ul class="org-children">
                    @foreach ($children as $child)
                        <x-public.structure-branch :unit="$child" :depth="$depth + 1" />
                    @endforeach
                </ul>
            @endif
        @endif
    </li>
@endif

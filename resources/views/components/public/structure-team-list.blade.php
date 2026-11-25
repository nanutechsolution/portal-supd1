@props(['units'])

<div class="org-team-list">
    @foreach ($units as $unit)
        @if ($unit->hasPosition())
            <div class="org-team-list__item">
                <span class="org-team-list__dot" aria-hidden="true"></span>

                <x-public.structure-card :unit="$unit" />

                @if (($unit->visibleChildren ?? collect())->isNotEmpty())
                    <x-public.structure-team-list :units="$unit->visibleChildren" />
                @endif
            </div>
        @endif
    @endforeach
</div>

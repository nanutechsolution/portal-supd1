@if ($appearance)
    @php
        $nonce = \Illuminate\Support\Facades\Vite::cspNonce();
    @endphp

    <script @if (filled($nonce)) nonce="{{ $nonce }}" @endif>
        (() => {
            const mode = @js($appearance->mode);
            const density = @js($appearance->density);
            const sidebarExpanded = @js($appearance->isSidebarExpanded());

            try {
                // Filament's own blocking script reads these keys before the
                // first paint, so the saved theme is applied without a flash.
                localStorage.setItem('filament:theme:mode', mode);
                localStorage.setItem('filament:sidebar:desktop_open', sidebarExpanded ? 'true' : 'false');
            } catch (error) {
                // Storage may be unavailable (for example in private browsing).
                // The panel then keeps Filament's safe defaults for this request.
            }

            document.documentElement.classList.toggle('fi-density-compact', density === 'compact');
        })();
    </script>
@endif

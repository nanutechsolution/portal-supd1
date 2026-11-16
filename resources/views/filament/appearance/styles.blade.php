<style>
    @php
        $sidebarPalette = $appearance?->sidebarPalette();
    @endphp

    @if ($sidebarPalette)
        /*
         * Independent sidebar tint. Scoped to Filament's sidebar hook so the
         * accent palette and every other panel surface stay untouched.
         */
        html.fi .fi-sidebar {
            background-color: {{ $sidebarPalette[100] }};
        }

        html.fi.dark .fi-sidebar {
            background-color: {{ $sidebarPalette[900] }};
        }

        /* The drawer header is visible below the desktop breakpoint. */
        html.fi .fi-body-has-topbar .fi-sidebar-header {
            background-color: {{ $sidebarPalette[100] }};
        }

        html.fi.dark .fi-body-has-topbar .fi-sidebar-header {
            background-color: {{ $sidebarPalette[900] }};
        }

        /* Keep secondary sidebar text and icons readable on the tinted surface. */
        html.fi:not(.dark) .fi-sidebar {
            --gray-400: {{ $sidebarPalette[600] }};
            --gray-500: {{ $sidebarPalette[700] }};
        }
    @endif

    /*
     * Density: targeted, class-scoped rules that follow Filament's own CSS hook
     * classes. Mobile spacing keeps Filament's defaults so stacked tables and
     * touch targets stay comfortable.
     */
    @media (min-width: 40rem) {
        html.fi-density-compact .fi-ta-text:not(.fi-inline) {
            padding-block: calc(var(--spacing) * 2);
        }

        html.fi-density-compact .fi-btn {
            padding-inline: calc(var(--spacing) * 2.5);
            padding-block: calc(var(--spacing) * 1.5);
        }

        html.fi-density-compact .fi-wi-stats-overview-stat {
            padding: calc(var(--spacing) * 4);
        }
    }

    html.fi-density-compact .fi-fo-field {
        row-gap: calc(var(--spacing) * 1.5);
    }

    /* Appearance settings page */
    .appearance-settings {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .appearance-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .appearance-intro {
        max-width: 60rem;
        font-size: 0.875rem;
        line-height: 1.6;
        color: var(--gray-600);
    }

    .dark .appearance-intro {
        color: var(--gray-400);
    }

    .appearance-status {
        border: 1px solid var(--success-300);
        border-radius: 0.75rem;
        background-color: var(--success-50);
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        color: var(--success-700);
    }

    .dark .appearance-status {
        border-color: color-mix(in oklab, var(--success-500) 45%, transparent);
        background-color: color-mix(in oklab, var(--success-500) 12%, transparent);
        color: var(--success-300);
    }

    .appearance-split {
        display: grid;
        gap: 1.25rem;
    }

    @media (min-width: 64rem) {
        .appearance-split {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .appearance-fieldset {
        margin: 0;
        border: 0;
        padding: 0;
        min-width: 0;
    }

    .appearance-legend {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip-path: inset(50%);
        white-space: nowrap;
        border: 0;
    }

    .appearance-options {
        display: grid;
        gap: 0.75rem;
    }

    .appearance-options-modes {
        grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    }

    .appearance-options-accents {
        grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr));
    }

    .appearance-options-compact,
    .appearance-options-sidebar {
        grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr));
    }

    .appearance-options-sidebar-colors {
        grid-template-columns: repeat(auto-fit, minmax(7.5rem, 1fr));
    }

    .appearance-card {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        background-color: #fff;
        padding: 0.75rem;
        cursor: pointer;
        transition: border-color 150ms ease, box-shadow 150ms ease;
    }

    .dark .appearance-card {
        border-color: color-mix(in oklab, #fff 12%, transparent);
        background-color: color-mix(in oklab, #fff 5%, transparent);
    }

    .appearance-card:hover {
        border-color: var(--gray-300);
    }

    .appearance-card:has(input:checked) {
        border-color: var(--primary-600);
        box-shadow: 0 0 0 1px var(--primary-600), 0 10px 26px -20px var(--primary-600);
    }

    .appearance-card:has(input:focus-visible) {
        outline: 2px solid var(--primary-600);
        outline-offset: 2px;
    }

    .appearance-card input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .appearance-card-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-950);
    }

    .dark .appearance-card-label {
        color: var(--color-white);
    }

    .appearance-card-check {
        flex-shrink: 0;
        width: 0.95rem;
        height: 0.95rem;
        border: 1.5px solid var(--gray-300);
        border-radius: 999px;
    }

    .dark .appearance-card-check {
        border-color: var(--gray-600);
    }

    .appearance-card:has(input:checked) .appearance-card-check {
        border-color: var(--primary-600);
        background-image: radial-gradient(circle, var(--primary-600) 0 45%, transparent 46%);
    }

    .appearance-card-description {
        font-size: 0.75rem;
        line-height: 1.45;
        color: var(--gray-500);
    }

    .dark .appearance-card-description {
        color: var(--gray-400);
    }

    .appearance-mock,
    .appearance-swatches,
    .appearance-density,
    .appearance-sidebar {
        display: flex;
        height: 4.25rem;
        overflow: hidden;
        border: 1px solid var(--gray-200);
        border-radius: 0.5rem;
    }

    .appearance-mock {
        background-color: #f8fafc;
    }

    .appearance-mock-sidebar {
        width: 24%;
        background-color: #0b4f63;
    }

    .appearance-mock-content {
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 0.35rem;
        background-color: #f8fafc;
        padding: 0.5rem;
    }

    .appearance-mock-bar {
        width: 55%;
        height: 0.35rem;
        border-radius: 999px;
        background-color: #94a3b8;
    }

    .appearance-mock-line {
        width: 100%;
        height: 0.3rem;
        border-radius: 999px;
        background-color: #cbd5e1;
    }

    .appearance-mock-line-short {
        width: 72%;
    }

    .appearance-mock-dark {
        border-color: #1e293b;
        background-color: #0f172a;
    }

    .appearance-mock-dark .appearance-mock-content {
        background-color: #0f172a;
    }

    .appearance-mock-dark .appearance-mock-bar {
        background-color: #64748b;
    }

    .appearance-mock-dark .appearance-mock-line {
        background-color: #334155;
    }

    .appearance-mock-system .appearance-mock-content {
        background-image: linear-gradient(90deg, #f8fafc 0 50%, #0f172a 50% 100%);
    }

    .appearance-mock-system .appearance-mock-line {
        background-image: linear-gradient(90deg, #cbd5e1 0 50%, #334155 50% 100%);
    }

    .appearance-swatches {
        border: 0;
    }

    .appearance-swatch {
        flex: 1;
    }

    .appearance-density {
        flex-direction: column;
        justify-content: center;
        gap: 0.4rem;
        background-color: #f8fafc;
        padding: 0.5rem 0.75rem;
    }

    .appearance-density-row {
        height: 0.4rem;
        border-radius: 999px;
        background-color: #cbd5e1;
    }

    .appearance-density-compact {
        gap: 0.2rem;
    }

    .appearance-density-compact .appearance-density-row {
        height: 0.3rem;
    }

    .dark .appearance-density {
        border-color: #1e293b;
        background-color: #0f172a;
    }

    .dark .appearance-density-row {
        background-color: #334155;
    }

    .appearance-sidebar {
        border-color: #1e293b;
        background-color: #0f172a;
    }

    .appearance-sidebar-panel {
        width: 42%;
        background-color: #0b4f63;
        transition: width 150ms ease;
    }

    .appearance-sidebar-collapsed .appearance-sidebar-panel {
        width: 18%;
    }

    .appearance-sidebar-content {
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 0.35rem;
        background-color: #f8fafc;
        padding: 0.5rem;
    }

    .dark .appearance-sidebar-content {
        background-color: #0f172a;
    }

    .appearance-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .appearance-hint {
        font-size: 0.8125rem;
        color: var(--gray-500);
    }

    .appearance-error {
        margin-top: 0.5rem;
        font-size: 0.8125rem;
        color: var(--danger-600);
    }

    .dark .appearance-error {
        color: var(--danger-400);
    }
</style>

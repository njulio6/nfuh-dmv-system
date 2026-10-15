@php
    $primaryLight = $appSettings->primary_color_light ?? '#18181b';
    $primaryDark = $appSettings->primary_color_dark ?? '#ffffff';
    $rgbLight = $appSettings->primary_rgb_light ?? '24, 24, 27';
    $rgbDark = $appSettings->primary_rgb_dark ?? '255, 255, 255';
    $textLight = $appSettings->primary_text_light ?? '#ffffff';
    $textDark = $appSettings->primary_text_dark ?? '#09090b';

    $secondaryLight = $appSettings->secondary_color_light ?? '#52525b';
    $secondaryDark = $appSettings->secondary_color_dark ?? '#a1a1aa';
    $secRgbLight = $appSettings->secondary_rgb_light ?? '82, 82, 91';
    $secRgbDark = $appSettings->secondary_rgb_dark ?? '161, 161, 170';
    $secTextLight = $appSettings->secondary_text_light ?? '#ffffff';
    $secTextDark = $appSettings->secondary_text_dark ?? '#09090b';
@endphp
<!-- Dynamic Theme Engine -->
<style id="dynamic-theme-styles">
    [x-cloak] {
        display: none !important;
    }

    :root {
        --brand-primary: {{ $primaryLight }};
        --brand-primary-rgb: {{ $rgbLight }};
        --brand-primary-text: {{ $textLight }};
        --brand-secondary: {{ $secondaryLight }};
        --brand-secondary-rgb: {{ $secRgbLight }};
        --brand-secondary-text: {{ $secTextLight }};
    }
    html.dark {
        --brand-primary: {{ $primaryDark }};
        --brand-primary-rgb: {{ $rgbDark }};
        --brand-primary-text: {{ $textDark }};
        --brand-secondary: {{ $secondaryDark }};
        --brand-secondary-rgb: {{ $secRgbDark }};
        --brand-secondary-text: {{ $secTextDark }};
    }
    .bg-brand-primary {
        background-color: var(--brand-primary) !important;
        color: var(--brand-primary-text) !important;
    }
    .bg-brand-primary * {
        color: var(--brand-primary-text) !important;
    }
    .bg-brand-subtle {
        background-color: rgba(var(--brand-primary-rgb), 0.12) !important;
        color: var(--brand-primary) !important;
    }
    .text-brand-primary {
        color: var(--brand-primary) !important;
    }
    .border-brand-primary {
        border-color: var(--brand-primary) !important;
    }
    .ring-brand-primary {
        --tw-ring-color: var(--brand-primary) !important;
    }
    .hover\:bg-brand-primary:hover,
    .btn-brand-primary:hover {
        filter: brightness(0.92);
        opacity: 0.95;
    }

    /* Secondary Theme Utilities */
    .bg-brand-secondary {
        background-color: var(--brand-secondary) !important;
        color: var(--brand-secondary-text) !important;
    }
    .bg-brand-secondary * {
        color: var(--brand-secondary-text) !important;
    }
    .bg-brand-secondary-subtle {
        background-color: rgba(var(--brand-secondary-rgb), 0.12) !important;
        color: var(--brand-secondary) !important;
    }
    .text-brand-secondary {
        color: var(--brand-secondary) !important;
    }
    .border-brand-secondary {
        border-color: var(--brand-secondary) !important;
    }
    .ring-brand-secondary {
        --tw-ring-color: var(--brand-secondary) !important;
    }
    .hover\:bg-brand-secondary:hover,
    .btn-brand-secondary:hover {
        filter: brightness(0.92);
        opacity: 0.95;
    }

    /* Secondary Brand Button */
    .btn-secondary-brand {
        background-color: rgba(var(--brand-secondary-rgb), 0.08) !important;
        color: var(--brand-secondary) !important;
        border: 1px solid rgba(var(--brand-secondary-rgb), 0.25) !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-secondary-brand:hover {
        background-color: var(--brand-secondary) !important;
        color: var(--brand-secondary-text) !important;
    }
    .btn-secondary-brand:hover * {
        color: var(--brand-secondary-text) !important;
    }

    /* Secondary Brand Badge */
    .badge-brand-secondary {
        background-color: rgba(var(--brand-secondary-rgb), 0.12) !important;
        color: var(--brand-secondary) !important;
        border: 1px solid rgba(var(--brand-secondary-rgb), 0.22) !important;
    }

    /* Sidebar active navigation items */
    #desktop-sidebar .group.bg-zinc-100.sidebar-text-primary,
    #mobile-sidebar .group.bg-zinc-100.sidebar-text-primary,
    html.dark #desktop-sidebar .group.bg-zinc-800.sidebar-text-primary,
    html.dark #mobile-sidebar .group.bg-zinc-800.sidebar-text-primary {
        background-color: rgba(var(--brand-primary-rgb), 0.12) !important;
        color: var(--brand-primary) !important;
        font-weight: 600 !important;
    }
    #desktop-sidebar .group.bg-zinc-100.sidebar-text-primary i,
    #desktop-sidebar .group.bg-zinc-100.sidebar-text-primary svg,
    #mobile-sidebar .group.bg-zinc-100.sidebar-text-primary i,
    #mobile-sidebar .group.bg-zinc-100.sidebar-text-primary svg,
    html.dark #desktop-sidebar .group.bg-zinc-800.sidebar-text-primary i,
    html.dark #desktop-sidebar .group.bg-zinc-800.sidebar-text-primary svg,
    html.dark #mobile-sidebar .group.bg-zinc-800.sidebar-text-primary i,
    html.dark #mobile-sidebar .group.bg-zinc-800.sidebar-text-primary svg {
        color: var(--brand-primary) !important;
    }

    /* Global primary action buttons override */
    a.bg-zinc-950.text-white.shadow-sm,
    button.bg-zinc-950.text-white.shadow-sm,
    a.bg-zinc-950.text-white.shadow-xs,
    button.bg-zinc-950.text-white.shadow-xs,
    a.bg-zinc-950.text-white.shadow-md,
    button.bg-zinc-950.text-white.shadow-md,
    html.dark a.dark\:bg-zinc-50.dark\:text-zinc-950.shadow-sm,
    html.dark button.dark\:bg-zinc-50.dark\:text-zinc-950.shadow-sm,
    html.dark a.dark\:bg-zinc-50.dark\:text-zinc-950.shadow-xs,
    html.dark button.dark\:bg-zinc-50.dark\:text-zinc-950.shadow-xs,
    html.dark a.dark\:bg-white.dark\:text-zinc-950.shadow-sm,
    html.dark button.dark\:bg-white.dark\:text-zinc-950.shadow-sm {
        background-color: var(--brand-primary) !important;
        color: var(--brand-primary-text) !important;
    }

    a.bg-zinc-950.text-white.shadow-sm:hover,
    button.bg-zinc-950.text-white.shadow-sm:hover,
    a.bg-zinc-950.text-white.shadow-xs:hover,
    button.bg-zinc-950.text-white.shadow-xs:hover,
    a.bg-zinc-950.text-white.shadow-md:hover,
    button.bg-zinc-950.text-white.shadow-md:hover,
    html.dark a.dark:bg-zinc-50.dark:text-zinc-950.shadow-sm:hover,
    html.dark button.dark:bg-zinc-50.dark:text-zinc-950.shadow-sm:hover,
    html.dark a.dark:bg-white.dark:text-zinc-950.shadow-sm:hover,
    html.dark button.dark:bg-white.dark:text-zinc-950.shadow-sm:hover {
        filter: brightness(0.92);
    }

    /* ─── Premium Frosted Glassmorphism System ─── */
    .glass-surface {
        background: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(20px) saturate(190%);
        -webkit-backdrop-filter: blur(20px) saturate(190%);
        border: 1px solid rgba(255, 255, 255, 0.85) !important;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 1px 3px 0 rgba(0, 0, 0, 0.02), inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    html.dark .glass-surface {
        background: rgba(25, 26, 27, 0.92) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 14px 36px -10px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
    }

    .glass-surface-interactive:hover {
        border-color: rgba(var(--brand-primary-rgb), 0.35) !important;
        box-shadow: 0 14px 34px -6px rgba(var(--brand-primary-rgb), 0.14), inset 0 1px 0 rgba(255, 255, 255, 1) !important;
    }

    html.dark .glass-surface-interactive:hover {
        border-color: rgba(var(--brand-primary-rgb), 0.45) !important;
        box-shadow: 0 18px 40px -10px rgba(0, 0, 0, 0.75), 0 0 24px -4px rgba(var(--brand-primary-rgb), 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.18) !important;
    }

    /* Glass Buttons & Controls - No transform/zoom */
    .glass-button {
        background: rgba(255, 255, 255, 0.65) !important;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(255, 255, 255, 0.85) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .glass-button:hover {
        background: rgba(255, 255, 255, 0.90) !important;
        border-color: rgba(var(--brand-primary-rgb), 0.35) !important;
        box-shadow: 0 4px 12px rgba(var(--brand-primary-rgb), 0.12), inset 0 1px 0 rgba(255, 255, 255, 1) !important;
    }

    html.dark .glass-button {
        background: rgba(42, 43, 45, 0.85) !important;
        border: 1px solid rgba(255, 255, 255, 0.10) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }

    html.dark .glass-button:hover {
        background: rgba(60, 61, 64, 0.95) !important;
        border-color: rgba(var(--brand-primary-rgb), 0.45) !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
    }

    /* ─── Premium Frosted Glass Stats Card (Ultra-Soft, Gentle Glass Gradient) ─── */
    .stat-card-gradient {
        position: relative;
        border-radius: 1rem;
        background: linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.012) 0%, rgba(255, 255, 255, 0.82) 50%, rgba(255, 255, 255, 0.90) 100%) !important;
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.88) !important;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.025), 0 1px 3px 0 rgba(0, 0, 0, 0.015), inset 0 1px 0 rgba(255, 255, 255, 1) !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    html.dark .stat-card-gradient {
        background: linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.025) 0%, rgba(25, 26, 27, 0.92) 50%, rgba(27, 28, 30, 0.95) 100%) !important;
        border: 1px solid rgba(255, 255, 255, 0.07) !important;
        box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
    }

    .stat-card-gradient:hover {
        background: linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.02) 0%, rgba(255, 255, 255, 0.88) 50%, rgba(255, 255, 255, 0.95) 100%) !important;
        border-color: rgba(255, 255, 255, 0.95) !important;
        box-shadow: 0 6px 22px -3px rgba(0, 0, 0, 0.035), inset 0 1px 0 rgba(255, 255, 255, 1) !important;
    }

    html.dark .stat-card-gradient:hover {
        background: linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.04) 0%, rgba(30, 31, 33, 0.95) 50%, rgba(32, 33, 35, 0.98) 100%) !important;
        border-color: rgba(255, 255, 255, 0.09) !important;
        box-shadow: 0 10px 28px -4px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.10) !important;
    }

    /* Elevated Stat Icon Badge - Soft refined appearance */
    .stat-icon-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        background-color: rgba(var(--brand-primary-rgb), 0.05) !important;
        border: 1px solid rgba(var(--brand-primary-rgb), 0.10) !important;
        color: var(--brand-primary) !important;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        transition: background-color 0.2s ease;
    }

    html.dark .stat-icon-badge {
        background-color: rgba(var(--brand-primary-rgb), 0.08) !important;
        border: 1px solid rgba(var(--brand-primary-rgb), 0.15) !important;
        color: var(--brand-primary) !important;
    }

    .stat-card-gradient:hover .stat-icon-badge {
        background-color: rgba(var(--brand-primary-rgb), 0.08) !important;
    }

    /* ─── System-wide Frosted Glass Overrides ─── */
    .glass-panel,
    .table-container,
    .filter-bar {
        background: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(20px) saturate(190%);
        -webkit-backdrop-filter: blur(20px) saturate(190%);
        border: 1px solid rgba(255, 255, 255, 0.85) !important;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
    }

    html.dark .glass-panel,
    html.dark .table-container,
    html.dark .filter-bar {
        background: rgba(25, 26, 27, 0.92) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 14px 36px -10px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
    }

    /* Modal dialog frosted glass */
    .modal-glass {
        background: rgba(255, 255, 255, 0.82) !important;
        backdrop-filter: blur(24px) saturate(200%);
        -webkit-backdrop-filter: blur(24px) saturate(200%);
        border: 1px solid rgba(255, 255, 255, 0.90) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.95) !important;
    }

    html.dark .modal-glass {
        background: rgba(25, 26, 27, 0.96) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.12) !important;
    }
</style>

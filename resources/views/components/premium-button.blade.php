@props(['variant' => 'primary', 'type' => 'button'])
@php
    $baseClass = 'inline-flex items-center justify-center rounded-lg text-sm font-bold px-4 py-2 transition-all cursor-pointer active:scale-95 gap-1.5 select-none';
    $variants = [
        'primary' => 'bg-zinc-950 text-white hover:bg-zinc-900 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-100 shadow-sm',
        'secondary' => 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 shadow-xs',
        'danger' => 'bg-red-600 text-white hover:bg-red-500 dark:bg-red-950/20 dark:text-red-400 border border-red-200/35 dark:border-red-900/40 shadow-xs'
    ];
    $class = $baseClass . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp
@if($attributes->has('href'))
    <a {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => $type, 'class' => $class]) }}>
        {{ $slot }}
    </button>
@endif

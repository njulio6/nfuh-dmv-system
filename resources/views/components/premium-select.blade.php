@props(['label' => null, 'required' => false, 'name' => null])
<div class="flex flex-col w-full">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <div class="relative w-full">
        <select 
            {{ $attributes->merge([
                'name' => $name,
                'id' => $name,
                'class' => 'w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all appearance-none cursor-pointer'
            ]) }}
            @if($required) required @endif
        >
            {{ $slot }}
        </select>
        <!-- Dropdown Chevron Arrow -->
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-zinc-400">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
        </div>
    </div>
</div>

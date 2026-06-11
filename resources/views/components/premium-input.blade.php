@props(['label' => null, 'required' => false, 'name' => null])
<div class="flex flex-col w-full">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <div class="relative w-full">
        <input 
            {{ $attributes->merge([
                'type' => 'text',
                'name' => $name,
                'id' => $name,
                'class' => 'w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all'
            ]) }}
            @if($required) required @endif
        >
    </div>
</div>

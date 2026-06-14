@props(['headers' => []])
<div class="w-full">
    <table class="w-full text-left border-collapse min-w-[900px]">
        <thead>
            <tr class="bg-[rgb(243,245,251)] dark:bg-zinc-950/40 border-b border-zinc-200/60 dark:border-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                @foreach($headers as $header)
                    @php
                        $isCenter = is_array($header) && isset($header['align']) && $header['align'] === 'center';
                        $isRight = is_array($header) && isset($header['align']) && $header['align'] === 'right';
                        $alignClass = $isCenter ? 'text-center' : ($isRight ? 'text-right' : 'text-left');
                        $widthClass = is_array($header) && isset($header['width']) ? $header['width'] : '';
                        $label = is_array($header) ? $header['label'] : $header;
                    @endphp
                    <th class="py-2.5 px-3 {{ $alignClass }} {{ $widthClass }}">
                        {{ $label }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/40 dark:divide-zinc-800/40 text-xs whitespace-nowrap">
            {{ $slot }}
        </tbody>
    </table>
</div>

@props(['items' => []])

<nav class="flex items-center text-xs text-gray-500 mb-3 space-x-1 sm:space-x-2" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}" class="hover:text-primary transition-colors inline-flex items-center gap-1 font-medium">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Dashboard</span>
    </a>

    @foreach($items as $key => $val)
        @php
            $label = is_array($val) ? ($val['label'] ?? '') : $key;
            $url = is_array($val) ? ($val['url'] ?? null) : $val;
        @endphp
        <svg class="w-3 h-3 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        @if($url && !$loop->last)
            <a href="{{ $url }}" class="hover:text-primary transition-colors font-medium truncate max-w-[150px] sm:max-w-xs">{{ $label }}</a>
        @else
            <span class="text-gray-900 font-semibold truncate max-w-[200px] sm:max-w-md">{{ $label }}</span>
        @endif
    @endforeach
</nav>


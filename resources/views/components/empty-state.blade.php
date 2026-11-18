@props([
    'title' => 'Belum Ada Data',
    'description' => 'Data untuk bagian ini belum tersedia saat ini.',
    'actionText' => null,
    'actionUrl' => null,
    'actionClick' => null,
])

<div class="bg-white rounded-2xl border border-gray-200 p-8 sm:p-12 text-center shadow-2xs">
    <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-4">
        {{ $slot->isEmpty() ? defaultIcon() : $slot }}
    </div>
    <h3 class="text-base font-bold text-gray-900 mb-1.5">{{ $title }}</h3>
    <p class="text-xs text-gray-500 max-w-md mx-auto leading-relaxed mb-6">{{ $description }}</p>

    @if($actionText)
        @if($actionUrl)
            <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-primary hover:bg-primary-dark text-white shadow-2xs transition-colors">
                <span>{{ $actionText }}</span>
            </a>
        @elseif($actionClick)
            <button type="button" @click="{{ $actionClick }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-primary hover:bg-primary-dark text-white shadow-2xs transition-colors">
                <span>{{ $actionText }}</span>
            </button>
        @endif
    @endif
</div>

@php
function defaultIcon() {
    return new \Illuminate\Support\HtmlString('
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
        </svg>
    ');
}
@endphp


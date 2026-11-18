<div 
    x-data="{
        messages: [],
        add(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.messages.push({ id, message, type });
            setTimeout(() => {
                this.remove(id);
            }, 4500);
        },
        remove(id) {
            this.messages = this.messages.filter(m => m.id !== id);
        }
    }"
    x-init="
        @if(session('success'))
            add({{ json_encode(session('success')) }}, 'success');
        @endif
        @if(session('error'))
            add({{ json_encode(session('error')) }}, 'error');
        @endif
        @if(session('info'))
            add({{ json_encode(session('info')) }}, 'info');
        @endif
    "
    @toast.window="add($event.detail.message, $event.detail.type || 'success')"
    class="fixed top-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
>
    <template x-for="item in messages" :key="item.id">
        <div 
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-10px] scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-[-10px] scale-95"
            class="pointer-events-auto p-4 rounded-xl shadow-lg border flex items-start gap-3 bg-white"
            :class="{
                'border-emerald-200 text-gray-800': item.type === 'success',
                'border-rose-200 text-gray-800': item.type === 'error',
                'border-indigo-200 text-gray-800': item.type === 'info'
            }"
        >
            <!-- Ikon Status -->
            <div class="shrink-0 mt-0.5">
                <template x-if="item.type === 'success'">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </template>
                <template x-if="item.type === 'error'">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </div>
                </template>
                <template x-if="item.type === 'info'">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </template>
            </div>

            <!-- Teks Notifikasi -->
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider mb-0.5" 
                   :class="{
                       'text-emerald-700': item.type === 'success',
                       'text-rose-700': item.type === 'error',
                       'text-indigo-700': item.type === 'info'
                   }"
                   x-text="item.type === 'success' ? 'Berhasil' : (item.type === 'error' ? 'Pemberitahuan' : 'Informasi')">
                </p>
                <p class="text-xs font-medium text-gray-700 leading-relaxed" x-text="item.message"></p>
            </div>

            <!-- Tombol Dismiss -->
            <button 
                @click="remove(item.id)" 
                class="shrink-0 text-gray-400 hover:text-gray-600 p-1 rounded-md hover:bg-gray-100 transition-colors"
                title="Tutup"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </template>
</div>


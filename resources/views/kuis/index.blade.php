<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kuis & Ujian') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-gray-900 text-lg">Siap untuk menguji kemampuanmu?</h3>
                <p class="text-sm text-gray-500">Kerjakan kuis tepat waktu untuk mendapatkan nilai maksimal.</p>
            </div>
            <span class="px-3 py-1 bg-amber-50 text-amber-700 text-sm font-semibold rounded-full border border-amber-200">
                2 Kuis Mendatang
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Quiz Card -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 w-2 h-full bg-primary"></div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider mb-1 block">Matematika Lanjut</span>
                        <h4 class="font-bold text-gray-900 text-xl">Ulangan Harian: Integral</h4>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Belum Dikerjakan
                    </span>
                </div>
                <div class="space-y-2 mb-6 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Durasi: 60 Menit
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Tenggat: Besok, 23:59
                    </div>
                    <div class="flex items-center gap-2 text-primary font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Monitoring Ujian Aktif
                    </div>
                </div>
                <a href="{{ route('kuis.kerjakan') }}" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    Kerjakan Sekarang
                </a>
            </div>

            <!-- Quiz Completed Card -->
            <div class="bg-gray-50 rounded-2xl p-6 shadow-sm border border-gray-100 relative overflow-hidden opacity-75">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 block">Biologi Sel</span>
                        <h4 class="font-bold text-gray-900 text-xl">Kuis: Struktur Sel</h4>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Selesai
                    </span>
                </div>
                <div class="space-y-2 mb-6 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Nilai: 85/100
                    </div>
                </div>
                <button disabled class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white cursor-not-allowed">
                    Lihat Pembahasan
                </button>
            </div>
        </div>
    </div>
</x-app-layout>

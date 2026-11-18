<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="['Kelas & Mata Pelajaran' => '']" />
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Kelas & Mata Pelajaran') }}
                </h2>
            </div>
            
            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
            <button x-data @click="$dispatch('open-modal', 'add-course')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-semibold text-xs shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Tambah Mata Pelajaran</span>
            </button>
            @elseif(Auth::user()->role === 'siswa')
            <button x-data @click="$dispatch('open-modal', 'join-course')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-semibold text-xs shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                <span>Gabung Kelas</span>
            </button>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        @php
            $gradients = [
                'from-blue-600 to-indigo-700',
                'from-teal-600 to-emerald-700',
                'from-indigo-600 to-purple-700',
                'from-slate-700 to-slate-900',
                'from-rose-600 to-orange-600',
                'from-cyan-600 to-blue-700',
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($courses ?? [] as $index => $course)
            @php
                $grad = $gradients[$index % count($gradients)];
                $studentsTotal = $course->students_count ?? $course->students()->count();
                $activitiesTotal = ($course->materials_count ?? $course->materials()->count()) + ($course->quizzes_count ?? $course->quizzes()->count());
            @endphp
            <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 overflow-hidden hover:shadow-md hover:border-gray-300 transition-all group flex flex-col relative">
                <!-- Header Card dengan Gradient -->
                <div class="h-36 bg-gradient-to-br {{ $grad }} p-5 flex flex-col justify-between relative text-white">
                    <div class="flex items-center justify-between z-10">
                        <span class="inline-block px-2.5 py-0.5 bg-white/20 backdrop-blur-md rounded-md text-[11px] font-bold uppercase tracking-wider text-white">
                            KELAS
                        </span>

                        @if(Auth::user()->role === 'admin' || (Auth::user()->role === 'guru' && $course->teacher_id === Auth::id()))
                        <!-- Dropdown Aksi Guru -->
                        <div x-data="{ openOptions: false }">
                            <button @click.prevent="openOptions = !openOptions" class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition-colors focus:outline-none" title="Menu Opsi">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                            </button>
                            <div x-show="openOptions" @click.away="openOptions = false" x-transition class="absolute right-3 top-12 mt-1 w-40 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 text-xs z-30" style="display: none;">
                                <a href="{{ route('kelas.show', $course->id) }}" class="block px-3.5 py-2 text-gray-700 hover:bg-gray-50 font-medium">Buka Kelas</a>
                                <form method="POST" action="{{ route('kelas.destroy', $course->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini beserta seluruh isinya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full text-left px-3.5 py-2 text-rose-600 hover:bg-rose-50 font-medium">Hapus Kelas</button>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="z-10">
                        <a href="{{ route('kelas.show', $course->id) }}" class="block">
                            <h4 class="font-bold text-lg text-white group-hover:underline line-clamp-1 leading-snug">{{ $course->title }}</h4>
                        </a>

                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                        <!-- Quick Copy Kode Undangan Kelas -->
                        <div x-data="{ copied: false }" class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 bg-black/25 backdrop-blur-md rounded-lg text-white/95 text-[11px] font-mono border border-white/10">
                            <span>Kode: <strong class="tracking-wider">{{ $course->invitation_code }}</strong></span>
                            <button type="button" @click.stop.prevent="navigator.clipboard.writeText('{{ $course->invitation_code }}'); copied = true; $dispatch('toast', { message: 'Kode kelas {{ $course->invitation_code }} berhasil disalin!', type: 'success' }); setTimeout(() => copied = false, 2000)" class="hover:text-amber-300 transition-colors p-0.5" title="Salin Kode Kelas">
                                <template x-if="!copied">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </template>
                                <template x-if="copied">
                                    <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                </template>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Konten & Footer Card -->
                <a href="{{ route('kelas.show', $course->id) }}" class="p-5 flex-1 flex flex-col justify-between">
                    <p class="text-xs text-gray-600 mb-4 line-clamp-2 leading-relaxed">
                        {{ $course->description ?: 'Tidak ada deskripsi tambahan untuk mata pelajaran ini.' }}
                    </p>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-[10px]">
                                {{ strtoupper(substr($course->teacher->name ?? 'G', 0, 1)) }}
                            </span>
                            <span class="font-medium text-gray-700 truncate max-w-[110px]">{{ $course->teacher->name ?? 'Guru' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-[11px] text-gray-400">
                            @if(Auth::user()->role !== 'siswa')
                            <span class="inline-flex items-center gap-1 font-medium text-gray-600">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                {{ $studentsTotal }}
                            </span>
                            <span>&bull;</span>
                            @endif
                            <span class="inline-flex items-center gap-1 font-medium text-gray-600">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                {{ $activitiesTotal }} Aktivitas
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-span-full">
                @if(Auth::user()->role === 'siswa')
                <x-empty-state 
                    title="Belum Mengikuti Mata Pelajaran" 
                    description="Anda belum terdaftar di kelas manapun. Masukkan kode undangan dari guru untuk mulai mengikuti materi dan tugas."
                    actionText="Gabung Kelas Sekarang"
                    actionClick="$dispatch('open-modal', 'join-course')"
                />
                @else
                <x-empty-state 
                    title="Belum Ada Mata Pelajaran" 
                    description="Anda belum memiliki mata pelajaran aktif. Buat kelas pertama Anda untuk mulai membagikan materi dan kuis kepada siswa."
                    actionText="Tambah Mata Pelajaran"
                    actionClick="$dispatch('open-modal', 'add-course')"
                />
                @endif
            </div>
            @endforelse
        </div>

        <!-- Modal Tambah Kelas -->
        <x-modal name="add-course" focusable>
            <form method="post" action="{{ route('kelas.store') }}" class="p-6">
                @csrf
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h2 class="text-base font-bold text-gray-900">
                        Tambah Mata Pelajaran Baru
                    </h2>
                    <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Mata Pelajaran</label>
                        <input type="text" name="title" required class="w-full text-sm rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" placeholder="Cth: Pemrograman Web 2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Deskripsi Singkat</label>
                        <textarea name="description" required rows="3" class="w-full text-sm rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" placeholder="Jelaskan ringkasan materi atau tujuan mata pelajaran ini..."></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-semibold shadow-2xs">
                        Simpan Mata Pelajaran
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Gabung Kelas (Siswa) -->
        @if(Auth::user()->role === 'siswa')
        <x-modal name="join-course" focusable>
            <form method="post" action="{{ route('kelas.join') }}" class="p-6">
                @csrf
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h2 class="text-base font-bold text-gray-900">
                        Gabung ke Mata Pelajaran
                    </h2>
                    <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kode Undangan Kelas</label>
                        <input type="text" name="code" required class="w-full text-base tracking-widest font-mono rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 uppercase text-center font-bold" placeholder="Cth: 8XF2A1" maxlength="10">
                        <p class="text-[11px] text-gray-400 mt-1.5">Minta kode 6 karakter ini kepada guru mata pelajaran Anda.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-semibold shadow-2xs">
                        Gabung Kelas
                    </button>
                </div>
            </form>
        </x-modal>
        @endif
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 leading-tight">
            {{ __('Kelas & Materi') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">Mata Pelajaran Aktif</h3>
            
            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
            <button x-data @click="$dispatch('open-modal', 'add-course')" class="px-4 py-2 bg-primary text-white rounded-lg font-medium text-sm hover:bg-primary-dark transition-colors shadow-sm">
                + Tambah Mata Pelajaran
            </button>
            @elseif(Auth::user()->role === 'siswa')
            <button x-data @click="$dispatch('open-modal', 'join-course')" class="px-4 py-2 bg-primary text-white rounded-lg font-medium text-sm hover:bg-primary-dark transition-colors shadow-sm">
                Gabung Kelas
            </button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($courses ?? [] as $course)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md hover:border-gray-300 transition-all group flex flex-col relative">
                <a href="{{ route('kelas.show', $course->id) }}" class="h-32 bg-primary p-4 flex flex-col justify-end relative">
                    <span class="inline-block px-2 py-1 bg-white/20 backdrop-blur-sm rounded text-xs font-semibold uppercase tracking-wider text-white w-fit mb-2">KELAS</span>
                    <h4 class="font-bold text-xl text-white group-hover:underline line-clamp-1">{{ $course->title }}</h4>
                </a>

                @if(Auth::user()->role === 'admin' || (Auth::user()->role === 'guru' && $course->teacher_id === Auth::id()))
                <!-- Dropdown Aksi Guru di Kartu -->
                <div class="absolute top-3 right-3 z-10" x-data="{ openOptions: false }">
                    <button @click.prevent="openOptions = !openOptions" class="p-1.5 rounded-full text-white/80 hover:text-white hover:bg-white/20 transition-colors focus:outline-none" title="Menu Opsi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                    </button>
                    <div x-show="openOptions" @click.away="openOptions = false" x-transition class="absolute right-0 mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-100 py-1 text-xs" style="display: none;">
                        <a href="{{ route('kelas.show', $course->id) }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 font-medium">Buka Kelas</a>
                        <form method="POST" action="{{ route('kelas.destroy', $course->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini beserta seluruh isinya?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full text-left px-3 py-2 text-red-600 hover:bg-red-50 font-medium">Hapus Kelas</button>
                        </form>
                    </div>
                </div>
                @endif

                <a href="{{ route('kelas.show', $course->id) }}" class="p-5 flex-1 flex flex-col">
                    <p class="text-sm text-gray-600 mb-4 line-clamp-2 flex-1">{{ $course->description }}</p>
                    <div class="flex items-center gap-2 text-xs font-medium text-gray-500 mt-auto">
                        <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                            {{ substr($course->teacher->name ?? 'G', 0, 1) }}
                        </span>
                        {{ $course->teacher->name ?? 'Guru' }}
                    </div>
                </a>
            </div>
            @empty
            <div class="col-span-full bg-white p-8 text-center rounded-xl border border-gray-200 text-gray-500">
                Belum ada mata pelajaran yang ditambahkan.
            </div>
            @endforelse
        </div>

        <!-- Modal Tambah Kelas -->
        <x-modal name="add-course" focusable>
            <form method="post" action="{{ route('kelas.store') }}" class="p-6">
                @csrf
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    Tambah Mata Pelajaran Baru
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mata Pelajaran</label>
                        <input type="text" name="title" required class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" placeholder="Cth: Fisika Dasar">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                        <textarea name="description" required rows="3" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">
                        Simpan Kelas
                    </button>
                </div>
            </form>
        </x-modal>
        <!-- Modal Gabung Kelas (Siswa) -->
        @if(Auth::user()->role === 'siswa')
        <x-modal name="join-course" focusable>
            <form method="post" action="{{ route('kelas.join') }}" class="p-6">
                @csrf
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    Gabung ke Mata Pelajaran
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Undangan</label>
                        <input type="text" name="code" required class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 uppercase font-mono" placeholder="Masukkan 6 karakter kode">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">
                        Gabung
                    </button>
                </div>
            </form>
        </x-modal>
        @endif
    </div>
</x-app-layout>

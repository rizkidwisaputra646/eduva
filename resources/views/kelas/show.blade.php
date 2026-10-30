<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                {{ $course->title ?? 'Detail Kelas' }}
            </h2>
            <div class="flex items-center gap-3">
                @if(Auth::user()->role === 'admin' || (Auth::user()->role === 'guru' && $course->teacher_id === Auth::id()))
                <button x-data @click="$dispatch('open-modal', 'edit-course-modal')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit Kelas
                </button>
                <button x-data @click="$dispatch('open-modal', 'delete-course-modal')" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus Kelas
                </button>
                @endif
                <a href="{{ route('kelas.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 ml-2">
                    &larr; Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="{ activeTab: 'classwork' }" class="max-w-5xl mx-auto space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <!-- Banner Kelas ala Google Classroom -->
        <div class="relative w-full h-48 md:h-64 rounded-xl md:rounded-2xl overflow-hidden shadow-sm mt-4">
            <!-- Background Cover -->
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
            <!-- Pattern Overlay (Optional aesthetic) -->
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>
            
            <div class="absolute bottom-0 left-0 p-6 md:p-8 w-full">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">{{ $course->title }}</h1>
                <p class="text-blue-100 text-lg">{{ $course->description ?? 'Deskripsi Mata Pelajaran' }}</p>
            </div>
            
            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
            <div class="absolute top-4 right-4 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-lg border border-white/30 text-white">
                <span class="text-sm font-medium opacity-80 block mb-1">Kode Kelas</span>
                <code class="font-mono font-bold text-xl select-all tracking-wider">{{ $course->invitation_code ?? '-' }}</code>
            </div>
            @endif
        </div>

        <!-- Tab Navigasi -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button @click="activeTab = 'stream'" 
                        :class="activeTab === 'stream' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" 
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    Forum
                </button>
                <button @click="activeTab = 'classwork'" 
                        :class="activeTab === 'classwork' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" 
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    Tugas Kelas
                </button>
                <button @click="activeTab = 'people'" 
                        :class="activeTab === 'people' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" 
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    Anggota
                </button>
            </nav>
        </div>

        <!-- Tab: Forum (Stream) -->
        <div x-show="activeTab === 'stream'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
                <!-- Info Box Kiri -->
                <div class="md:col-span-1 hidden md:block">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <h3 class="font-bold text-gray-900 mb-2">Mendatang</h3>
                        @php
                            $upcoming = $course->quizzes->where('deadline', '>', now())->sortBy('deadline')->take(3);
                        @endphp
                        @forelse($upcoming as $task)
                            <div class="mb-3 last:mb-0">
                                <p class="text-sm font-medium text-gray-800 line-clamp-1">{{ $task->title }}</p>
                                <p class="text-xs text-red-500">{{ \Carbon\Carbon::parse($task->deadline)->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 mb-4">Hore, tidak ada tugas yang perlu segera diselesaikan!</p>
                        @endforelse
                        <a href="#" @click.prevent="activeTab = 'classwork'" class="text-sm font-medium text-primary hover:underline block mt-2">Lihat semua</a>
                    </div>
                </div>
                
                <!-- Main Stream -->
                <div class="md:col-span-3 space-y-4">
                    @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                    <!-- Pengumuman Input -->
                    <div x-data="{ expanded: false }" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all">
                        <div x-show="!expanded" @click="expanded = true" class="p-4 flex items-center gap-4 cursor-text hover:shadow-md transition-shadow">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-primary font-bold">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="flex-1 text-gray-500">
                                Umumkan sesuatu ke kelas Anda...
                            </div>
                        </div>
                        <div x-show="expanded" class="p-4" style="display: none;">
                            <form action="{{ route('announcements.store', $course->id) }}" method="POST">
                                @csrf
                                <textarea name="content" rows="4" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="Umumkan sesuatu ke kelas Anda..." required></textarea>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" @click="expanded = false" class="px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg text-sm font-medium">Batal</button>
                                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">Posting</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Post List -->
                    @forelse($course->announcements as $announcement)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-primary font-bold">
                                    {{ substr($announcement->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm">{{ $announcement->user->name }}</h4>
                                    <p class="text-xs text-gray-500">{{ $announcement->created_at->format('d M Y, H:i') }}</p>
                                </div>
                            </div>

                            @if(Auth::user()->role === 'admin' || $announcement->user_id === Auth::id())
                            <div class="flex items-center gap-1">
                                <button x-data @click="$dispatch('open-modal', 'edit-announcement-{{ $announcement->id }}')" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Edit Postingan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button x-data @click="$dispatch('open-modal', 'delete-announcement-{{ $announcement->id }}')" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Postingan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>

                            <!-- Modal Edit Pengumuman -->
                            <x-modal name="edit-announcement-{{ $announcement->id }}" focusable>
                                <form method="post" action="{{ route('announcements.update', $announcement->id) }}" class="p-6">
                                    @csrf
                                    @method('PUT')
                                    <h2 class="text-lg font-bold text-gray-900 mb-4">Edit Postingan Forum</h2>

                                    <div class="space-y-4">
                                        <textarea name="content" rows="4" required class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">{{ $announcement->content }}</textarea>
                                    </div>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">Batal</button>
                                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm font-medium">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </x-modal>

                            <!-- Modal Hapus Pengumuman -->
                            <x-modal name="delete-announcement-{{ $announcement->id }}" focusable>
                                <form method="post" action="{{ route('announcements.destroy', $announcement->id) }}" class="p-6">
                                    @csrf
                                    @method('DELETE')
                                    <h2 class="text-lg font-bold text-gray-900 mb-2">Hapus Postingan</h2>
                                    <p class="text-sm text-gray-600 mb-6">Apakah Anda yakin ingin menghapus postingan forum ini?</p>

                                    <div class="flex justify-end gap-3">
                                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">Batal</button>
                                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">Ya, Hapus</button>
                                    </div>
                                </form>
                            </x-modal>
                            @endif
                        </div>
                        <div class="text-gray-800 text-sm whitespace-pre-wrap leading-relaxed">{{ $announcement->content }}</div>
                    </div>
                    @empty
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center text-gray-500">
                        Belum ada postingan di forum kelas ini.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Tab: Tugas Kelas (Topic Outline ala SPADA / Moodle) -->
        <div x-show="activeTab === 'classwork'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-6 space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <div>
                    <h3 class="font-bold text-gray-900 text-lg">Alur Pembelajaran (Topic Outline)</h3>
                    <p class="text-xs text-gray-500">Struktur materi, diskusi forum, penugasan, dan presensi terstruktur per pertemuan.</p>
                </div>
                
                @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                <div class="flex items-center gap-2">
                    <button x-data @click="$dispatch('open-modal', 'add-activity')" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold text-xs hover:bg-primary-dark transition-all shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Aktivitas
                    </button>
                </div>
                @endif
            </div>

            <div class="space-y-4 mb-8">
                @forelse($groupedActivities as $topic => $activities)
                <div x-data="{ open: true }" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all">
                    <!-- Accordion Topic Header -->
                    <div class="flex items-center justify-between bg-slate-50/80 px-5 py-4 border-b border-gray-200 hover:bg-slate-100/80 transition-colors">
                        <button @click="open = !open" class="flex-1 text-left flex items-center gap-3 group focus:outline-none">
                            <span class="p-1 rounded-md text-gray-400 group-hover:text-primary transition-colors">
                                <svg class="w-5 h-5 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </span>
                            <div>
                                <h4 class="font-bold text-gray-900 text-base md:text-lg group-hover:text-primary transition-colors">
                                    {{ $topic }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5">{{ count($activities) }} Aktivitas Pembelajaran</p>
                            </div>
                        </button>
                        
                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                        <div class="flex items-center gap-2">
                            <button x-data @click="$dispatch('open-modal', 'add-activity'); $nextTick(() => { document.getElementById('activity_topic_select').value = '{{ addslashes($topic) }}'; })" class="text-xs px-2.5 py-1.5 bg-white border border-gray-200 text-gray-700 hover:text-primary hover:border-primary rounded-lg transition-colors font-medium flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Tambah di Sini
                            </button>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Accordion Content List -->
                    <div x-show="open" x-transition class="divide-y divide-gray-100">
                        @foreach($activities as $item)
                            @if($item->activity_type === 'material')
                                @if($item->type === 'label')
                                    <!-- A. LABEL / TEKS INTRO & CLOSING -->
                                    <div class="p-4 md:px-6 bg-amber-50/20 hover:bg-amber-50/40 transition-colors flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-3">
                                            <span class="p-1.5 rounded-lg bg-amber-100 text-amber-700 flex-shrink-0 mt-0.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                            </span>
                                            <div>
                                                <h5 class="text-xs font-bold text-amber-800 uppercase tracking-wider">{{ $item->title }}</h5>
                                                <div class="prose prose-sm max-w-none text-gray-700 text-sm mt-1 whitespace-pre-line leading-relaxed">
                                                    {{ $item->content }}
                                                </div>
                                            </div>
                                        </div>

                                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <form method="POST" action="{{ route('materials.toggle', $item->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="p-1 text-gray-400 hover:text-gray-700 rounded" title="{{ $item->is_visible ? 'Sembunyikan dari siswa' : 'Tampilkan ke siswa' }}">
                                                    @if($item->is_visible)
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    @else
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <button x-data @click="$dispatch('open-modal', 'edit-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-gray-700 rounded" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button x-data @click="$dispatch('open-modal', 'delete-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-red-600 rounded" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                @elseif($item->type === 'video')
                                    <!-- D. VIDEO PEMBELAJARAN -->
                                    <div class="p-4 md:px-6 hover:bg-gray-50/70 transition-colors flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-3 flex-1">
                                            <span class="p-2 rounded-lg bg-red-100 text-red-600 flex-shrink-0 mt-0.5">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </span>
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h5 class="font-bold text-gray-900 text-sm hover:text-primary transition-colors">{{ $item->title }}</h5>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">Video</span>
                                                    @if(!$item->is_visible)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">Disembunyikan</span>
                                                    @endif
                                                    <a href="{{ route('ai-tutor.index', ['material_id' => $item->id, 'course_id' => $course->id]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 text-primary hover:bg-primary hover:text-white transition-colors" title="Pelajari materi ini bersama AI Tutor">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                        Tanya AI
                                                    </a>
                                                </div>
                                                @if($item->content)
                                                <p class="text-xs text-gray-600 mt-1">{{ $item->content }}</p>
                                                @endif
                                                @if($item->video_url)
                                                <div class="mt-3 max-w-xl">
                                                    @php
                                                        $videoUrl = $item->video_url;
                                                        $embedUrl = null;
                                                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $videoUrl, $matches)) {
                                                            $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $matches[1];
                                                        }
                                                    @endphp
                                                    @if($embedUrl)
                                                    <div class="aspect-video w-full rounded-xl overflow-hidden shadow-sm border border-gray-200">
                                                        <iframe class="w-full h-full" src="{{ $embedUrl }}" frameborder="0" allowfullscreen></iframe>
                                                    </div>
                                                    @else
                                                    <a href="{{ $videoUrl }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-700 underline bg-red-50 px-3 py-1.5 rounded-lg border border-red-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                        Buka Video Pembelajaran
                                                    </a>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <form method="POST" action="{{ route('materials.toggle', $item->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="p-1 text-gray-400 hover:text-gray-700 rounded" title="{{ $item->is_visible ? 'Sembunyikan' : 'Tampilkan' }}">
                                                    @if($item->is_visible)
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    @else
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <button x-data @click="$dispatch('open-modal', 'edit-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-gray-700 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                            <button x-data @click="$dispatch('open-modal', 'delete-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-red-600 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                        </div>
                                        @endif
                                    </div>
                                @elseif($item->type === 'page')
                                    <!-- C. HALAMAN MATERI (ARTICLE / PAGE) -->
                                    <div class="p-4 md:px-6 hover:bg-gray-50/70 transition-colors flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-3 flex-1">
                                            <span class="p-2 rounded-lg bg-teal-100 text-teal-700 flex-shrink-0 mt-0.5">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                            </span>
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h5 class="font-bold text-gray-900 text-sm">{{ $item->title }}</h5>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">Halaman Materi</span>
                                                    @if(!$item->is_visible)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">Disembunyikan</span>
                                                    @endif
                                                    <a href="{{ route('ai-tutor.index', ['material_id' => $item->id, 'course_id' => $course->id]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 text-primary hover:bg-primary hover:text-white transition-colors" title="Pelajari materi ini bersama AI Tutor">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                        Tanya AI
                                                    </a>
                                                </div>
                                                <div class="prose prose-sm max-w-none text-gray-700 text-xs mt-2 bg-gray-50/80 p-3 rounded-lg border border-gray-100 whitespace-pre-line leading-relaxed">
                                                    {{ $item->content }}
                                                </div>
                                            </div>
                                        </div>

                                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <form method="POST" action="{{ route('materials.toggle', $item->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="p-1 text-gray-400 hover:text-gray-700 rounded" title="{{ $item->is_visible ? 'Sembunyikan' : 'Tampilkan' }}">
                                                    @if($item->is_visible)
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    @else
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <button x-data @click="$dispatch('open-modal', 'edit-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-gray-700 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                            <button x-data @click="$dispatch('open-modal', 'delete-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-red-600 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                        </div>
                                        @endif
                                    </div>
                                @else
                                    <!-- B. FILE MATERI (PDF/PPT/DOC) -->
                                    <div class="p-4 md:px-6 hover:bg-gray-50/70 transition-colors flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-3 flex-1">
                                            <span class="p-2 rounded-lg bg-emerald-100 text-emerald-700 flex-shrink-0 mt-0.5">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </span>
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h5 class="font-bold text-gray-900 text-sm">{{ $item->title }}</h5>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">File Materi</span>
                                                    @if(!$item->is_visible)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">Disembunyikan</span>
                                                    @endif
                                                    <a href="{{ route('ai-tutor.index', ['material_id' => $item->id, 'course_id' => $course->id]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 text-primary hover:bg-primary hover:text-white transition-colors" title="Pelajari materi ini bersama AI Tutor">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                        Tanya AI
                                                    </a>
                                                </div>
                                                @if($item->content)
                                                <p class="text-xs text-gray-600 mt-1">{{ $item->content }}</p>
                                                @endif
                                                @if($item->file_path)
                                                <div class="mt-2">
                                                    <a href="{{ Storage::url($item->file_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg text-xs font-semibold border border-emerald-200 transition-colors">
                                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                        Unduh / Buka Dokumen ({{ strtoupper(pathinfo($item->file_path, PATHINFO_EXTENSION)) }})
                                                    </a>
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <form method="POST" action="{{ route('materials.toggle', $item->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="p-1 text-gray-400 hover:text-gray-700 rounded" title="{{ $item->is_visible ? 'Sembunyikan' : 'Tampilkan' }}">
                                                    @if($item->is_visible)
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    @else
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <button x-data @click="$dispatch('open-modal', 'edit-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-gray-700 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                            <button x-data @click="$dispatch('open-modal', 'delete-material-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-red-600 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                        </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Modal Edit Material Generic -->
                                @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                <x-modal name="edit-material-{{ $item->id }}" focusable>
                                    <form method="post" action="{{ route('materials.update', $item->id) }}" enctype="multipart/form-data" class="p-6">
                                        @csrf
                                        @method('PUT')
                                        <h2 class="text-lg font-bold text-gray-900 mb-4">Edit Aktivitas ({{ ucfirst($item->type) }})</h2>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Topik / Pertemuan</label>
                                                <input type="text" name="topic" value="{{ $item->topic }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul</label>
                                                <input type="text" name="title" value="{{ $item->title }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                                            </div>
                                            @if($item->type === 'video')
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">URL Video (YouTube)</label>
                                                <input type="url" name="video_url" value="{{ $item->video_url }}" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                                            </div>
                                            @endif
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Konten / Instruksi</label>
                                                <textarea name="content" rows="4" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">{{ $item->content }}</textarea>
                                            </div>
                                            @if($item->type === 'file')
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ganti Berkas (Opsional)</label>
                                                <input type="file" name="document" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-primary">
                                            </div>
                                            @endif
                                        </div>
                                        <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-xs font-medium">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </x-modal>

                                <x-modal name="delete-material-{{ $item->id }}" focusable>
                                    <form method="post" action="{{ route('materials.destroy', $item->id) }}" class="p-6">
                                        @csrf
                                        @method('DELETE')
                                        <h2 class="text-lg font-bold text-gray-900 mb-2">Hapus Aktivitas</h2>
                                        <p class="text-sm text-gray-600 mb-6">Apakah Anda yakin ingin menghapus <strong>"{{ $item->title }}"</strong>?</p>
                                        <div class="flex justify-end gap-3">
                                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-medium">Hapus</button>
                                        </div>
                                    </form>
                                </x-modal>
                                @endif

                            @elseif($item->activity_type === 'quiz' || $item->activity_type === 'assignment')
                                <!-- F. TUGAS / ASSIGNMENT & KUIS -->
                                <div class="p-4 md:px-6 hover:bg-gray-50/70 transition-colors flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3 flex-1">
                                        <span class="p-2 rounded-lg bg-{{ $item->activity_type === 'quiz' ? 'blue' : 'purple' }}-100 text-{{ $item->activity_type === 'quiz' ? 'blue' : 'purple' }}-600 flex-shrink-0 mt-0.5">
                                            @if($item->activity_type === 'quiz')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                            @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            @endif
                                        </span>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <h5 class="font-bold text-gray-900 text-sm">{{ $item->title }}</h5>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-{{ $item->activity_type === 'quiz' ? 'blue' : 'purple' }}-50 text-{{ $item->activity_type === 'quiz' ? 'blue' : 'purple' }}-700 border border-{{ $item->activity_type === 'quiz' ? 'blue' : 'purple' }}-200">
                                                    {{ $item->activity_type === 'quiz' ? 'Kuis Online' : 'Tugas / Assignment' }}
                                                </span>
                                                @if(!$item->is_visible)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">Disembunyikan</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-600 mt-1">{{ $item->description }}</p>
                                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                                @if($item->deadline)
                                                <span class="flex items-center gap-1 text-rose-600 font-medium">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    Tenggat: {{ $item->deadline->format('d M Y, H:i') }} WIB
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                            @if($item->activity_type === 'quiz')
                                            <a href="{{ route('quizzes.show', $item->id) }}" class="text-xs px-2.5 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-medium">Kelola Soal</a>
                                            <a href="{{ url('assignments/'.$item->id.'/submissions') }}" class="text-xs px-2.5 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg font-medium">Hasil Kuis</a>
                                            @else
                                            <a href="{{ url('assignments/'.$item->id.'/submissions') }}" class="text-xs px-2.5 py-1.5 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg font-medium">Lihat Pengumpulan</a>
                                            @endif
                                            
                                            <form method="POST" action="{{ route('quizzes.toggle', $item->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="p-1 text-gray-400 hover:text-gray-700 rounded" title="{{ $item->is_visible ? 'Sembunyikan' : 'Tampilkan' }}">
                                                    @if($item->is_visible)
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    @else
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <button x-data @click="$dispatch('open-modal', 'edit-quiz-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-gray-700 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                            <button x-data @click="$dispatch('open-modal', 'delete-quiz-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-red-600 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                        @else
                                            @if($item->submissions->isNotEmpty())
                                                <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold inline-flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Sudah Dikumpulkan
                                                </span>
                                            @else
                                                @if($item->activity_type === 'quiz')
                                                <a href="{{ url('kuis/'.$item->id.'/kerjakan') }}" class="text-xs px-3.5 py-1.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">Kerjakan Kuis</a>
                                                @else
                                                <button x-data @click="$dispatch('open-modal', 'submit-assignment-{{ $item->id }}')" class="text-xs px-3.5 py-1.5 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700">Unggah Tugas</button>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                <x-modal name="edit-quiz-{{ $item->id }}" focusable>
                                    <form method="post" action="{{ route('quizzes.update', $item->id) }}" class="p-6">
                                        @csrf
                                        @method('PUT')
                                        <h2 class="text-lg font-bold text-gray-900 mb-4">Edit {{ $item->activity_type === 'quiz' ? 'Kuis' : 'Tugas' }}</h2>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Topik / Pertemuan</label>
                                                <input type="text" name="topic" value="{{ $item->topic }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul</label>
                                                <input type="text" name="title" value="{{ $item->title }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Instruksi / Deskripsi</label>
                                                <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">{{ $item->description }}</textarea>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tenggat Waktu</label>
                                                <input type="datetime-local" name="deadline" value="{{ $item->deadline ? $item->deadline->format('Y-m-d\TH:i') : '' }}" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                                            </div>
                                        </div>
                                        <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-xs font-medium">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </x-modal>

                                <x-modal name="delete-quiz-{{ $item->id }}" focusable>
                                    <form method="post" action="{{ route('quizzes.destroy', $item->id) }}" class="p-6">
                                        @csrf
                                        @method('DELETE')
                                        <h2 class="text-lg font-bold text-gray-900 mb-2">Hapus Aktivitas</h2>
                                        <p class="text-sm text-gray-600 mb-6">Apakah Anda yakin ingin menghapus <strong>"{{ $item->title }}"</strong>?</p>
                                        <div class="flex justify-end gap-3">
                                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-medium">Hapus</button>
                                        </div>
                                    </form>
                                </x-modal>
                                @endif

                                @if(Auth::user()->role === 'siswa' && $item->activity_type === 'assignment')
                                <x-modal name="submit-assignment-{{ $item->id }}" focusable>
                                    <form method="post" action="{{ url('assignments/'.$item->id.'/submit') }}" enctype="multipart/form-data" class="p-6">
                                        @csrf
                                        <h2 class="text-lg font-bold text-gray-900 mb-2">Unggah Tugas: {{ $item->title }}</h2>
                                        <p class="text-xs text-gray-500 mb-4">{{ $item->description }}</p>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Berkas Tugas (PDF, DOCX, ZIP)</label>
                                            <input type="file" name="submission_file" required accept=".pdf,.doc,.docx,.zip,.rar" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700">
                                        </div>
                                        <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-medium">Kumpulkan</button>
                                        </div>
                                    </form>
                                </x-modal>
                                @endif

                            @elseif($item->activity_type === 'attendance')
                                <!-- G. ABSENSI (PRESENSI) -->
                                <div class="p-4 md:px-6 hover:bg-gray-50/70 transition-colors flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3 flex-1">
                                        <span class="p-2 rounded-lg bg-emerald-100 text-emerald-600 flex-shrink-0 mt-0.5">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        </span>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <h5 class="font-bold text-gray-900 text-sm">{{ $item->title }}</h5>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Presensi Kelas</span>
                                                @if($item->is_active && now() >= $item->start_time && now() <= $item->end_time)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500 text-white animate-pulse">Sesi Terbuka</span>
                                                @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">Sesi Ditutup</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-600 mt-1">Periode: {{ $item->start_time->format('d M Y, H:i') }} - {{ $item->end_time->format('H:i') }} WIB</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                            <a href="{{ route('attendances.report', $item->id) }}" class="text-xs px-2.5 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg font-medium">Rekap Kehadiran</a>
                                            <form method="POST" action="{{ route('attendances.toggle-status', $item->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-xs px-2 py-1.5 border rounded-lg font-medium {{ $item->is_active ? 'border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                                    {{ $item->is_active ? 'Tutup' : 'Buka' }}
                                                </button>
                                            </form>
                                            <button x-data @click="$dispatch('open-modal', 'delete-attendance-{{ $item->id }}')" class="p-1 text-gray-400 hover:text-red-600 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                        @else
                                            @php
                                                $studentAtt = $item->logs->first();
                                            @endphp
                                            @if($studentAtt)
                                                <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold capitalize">
                                                    Status: {{ $studentAtt->status }}
                                                </span>
                                            @else
                                                @if($item->is_active && now() >= $item->start_time && now() <= $item->end_time)
                                                <button x-data @click="$dispatch('open-modal', 'submit-attendance-{{ $item->id }}')" class="text-xs px-3.5 py-1.5 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700">Isi Presensi</button>
                                                @else
                                                <span class="text-xs text-gray-400 italic">Presensi Ditutup</span>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                @if(Auth::user()->role === 'siswa')
                                <x-modal name="submit-attendance-{{ $item->id }}" focusable>
                                    <form method="post" action="{{ route('attendances.submit', $item->id) }}" class="p-6">
                                        @csrf
                                        <h2 class="text-lg font-bold text-gray-900 mb-2">Presensi Kehadiran: {{ $item->title }}</h2>
                                        <p class="text-xs text-gray-500 mb-4">{{ $item->topic }} &bull; Waktu: {{ now()->format('H:i') }} WIB</p>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Status Kehadiran</label>
                                                <div class="grid grid-cols-3 gap-2">
                                                    <label class="cursor-pointer border rounded-lg p-2.5 text-center hover:bg-emerald-50 border-gray-200 flex flex-col items-center">
                                                        <input type="radio" name="status" value="hadir" checked class="text-primary mb-1">
                                                        <span class="text-xs font-bold text-gray-800">Hadir</span>
                                                    </label>
                                                    <label class="cursor-pointer border rounded-lg p-2.5 text-center hover:bg-blue-50 border-gray-200 flex flex-col items-center">
                                                        <input type="radio" name="status" value="izin" class="text-primary mb-1">
                                                        <span class="text-xs font-bold text-gray-800">Izin</span>
                                                    </label>
                                                    <label class="cursor-pointer border rounded-lg p-2.5 text-center hover:bg-amber-50 border-gray-200 flex flex-col items-center">
                                                        <input type="radio" name="status" value="sakit" class="text-primary mb-1">
                                                        <span class="text-xs font-bold text-gray-800">Sakit</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan / Keterangan (Opsional)</label>
                                                <input type="text" name="note" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs" placeholder="Misal: Hadir tepat waktu">
                                            </div>
                                        </div>

                                        <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">Kirim Presensi</button>
                                        </div>
                                    </form>
                                </x-modal>
                                @endif

                                @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                <x-modal name="delete-attendance-{{ $item->id }}" focusable>
                                    <form method="post" action="{{ route('attendances.destroy', $item->id) }}" class="p-6">
                                        @csrf
                                        @method('DELETE')
                                        <h2 class="text-lg font-bold text-gray-900 mb-2">Hapus Sesi Presensi</h2>
                                        <p class="text-sm text-gray-600 mb-6">Apakah Anda yakin ingin menghapus presensi <strong>"{{ $item->title }}"</strong>?</p>
                                        <div class="flex justify-end gap-3">
                                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-medium">Hapus</button>
                                        </div>
                                    </form>
                                </x-modal>
                                @endif
                            @endif
                        @endforeach

                        <!-- E. FORUM DISKUSI TERINTEGRASI PADA TOPIK / PERTEMUAN -->
                        <div class="p-4 md:px-6 bg-slate-50/50 hover:bg-slate-100/60 transition-colors flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-lg bg-indigo-100 text-indigo-700 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                                </span>
                                <div>
                                    <h5 class="font-bold text-gray-900 text-sm">Forum Diskusi: {{ $topic }}</h5>
                                    <p class="text-xs text-gray-500">Ruang diskusi tanya jawab materi dan instruksi kelas.</p>
                                </div>
                            </div>
                            <button @click="activeTab = 'stream'" class="px-3 py-1.5 bg-white border border-gray-200 text-primary hover:bg-indigo-50 hover:border-primary rounded-lg text-xs font-semibold transition-colors flex items-center gap-1">
                                Masuk Forum &rarr;
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-white p-12 text-center rounded-2xl border border-gray-200 text-gray-500 mt-4">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Belum Ada Pertemuan atau Topik</h3>
                    <p class="text-sm">Klik tombol <strong>"Tambah Aktivitas"</strong> di atas untuk membuat sesi pembelajaran pertama Anda.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Tab: Anggota (People) -->
        <div x-show="activeTab === 'people'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" class="mt-6 space-y-8">
            <!-- Pengajar -->
            <div>
                <h3 class="text-xl font-bold text-primary mb-4 pb-2 border-b-2 border-primary">Pengajar</h3>
                <div class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-xl transition-colors">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-primary font-bold">
                        {{ substr($course->teacher->name ?? 'G', 0, 1) }}
                    </div>
                    <span class="font-medium text-gray-900 text-base">{{ $course->teacher->name ?? 'Guru' }}</span>
                </div>
            </div>

            <!-- Siswa -->
            <div>
                <div class="flex justify-between items-center mb-4 pb-2 border-b-2 border-primary">
                    <h3 class="text-xl font-bold text-primary">Siswa Terdaftar</h3>
                    <span class="text-primary font-medium text-sm">{{ $course->students->count() }} siswa</span>
                </div>
                
                <div class="space-y-2">
                    @forelse($course->students as $student)
                        <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-xl transition-colors border-b border-gray-100 last:border-0">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold">
                                    {{ substr($student->name, 0, 1) }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 block">{{ $student->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $student->email }}</span>
                                </div>
                            </div>
                            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                            <form method="POST" action="{{ route('kelas.students.remove', [$course->id, $student->id]) }}" onsubmit="return confirm('Keluarkan siswa {{ $student->name }} dari kelas ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs px-2.5 py-1 text-red-600 hover:bg-red-50 rounded-lg font-medium border border-red-200 transition-colors">
                                    Keluarkan
                                </button>
                            </form>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            Belum ada siswa yang bergabung.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Modal Tambah Aktivitas Unified (SPADA/Moodle Style) -->
        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
        <x-modal name="add-activity" focusable>
            <div x-data="{ activityType: 'file' }" class="p-6 max-h-[85vh] overflow-y-auto">
                <h2 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    Tambah Aktivitas & Sumber Belajar
                </h2>
                
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">Pilih Tipe Aktivitas</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5">
                        <label class="cursor-pointer border rounded-xl p-3 flex flex-col items-center text-center transition-all" :class="activityType === 'label' ? 'border-amber-500 ring-2 ring-amber-500 bg-amber-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" x-model="activityType" value="label" class="sr-only">
                            <span class="p-1.5 rounded-lg bg-amber-100 text-amber-700 mb-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg></span>
                            <span class="font-bold text-xs text-gray-900">Label / Pengantar</span>
                            <span class="text-[10px] text-gray-500">Opening/Closing</span>
                        </label>
                        <label class="cursor-pointer border rounded-xl p-3 flex flex-col items-center text-center transition-all" :class="activityType === 'file' ? 'border-emerald-500 ring-2 ring-emerald-500 bg-emerald-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" x-model="activityType" value="file" class="sr-only">
                            <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700 mb-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></span>
                            <span class="font-bold text-xs text-gray-900">Berkas Materi</span>
                            <span class="text-[10px] text-gray-500">PDF, PPTX, Word</span>
                        </label>
                        <label class="cursor-pointer border rounded-xl p-3 flex flex-col items-center text-center transition-all" :class="activityType === 'page' ? 'border-teal-500 ring-2 ring-teal-500 bg-teal-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" x-model="activityType" value="page" class="sr-only">
                            <span class="p-1.5 rounded-lg bg-teal-100 text-teal-700 mb-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg></span>
                            <span class="font-bold text-xs text-gray-900">Halaman Materi</span>
                            <span class="text-[10px] text-gray-500">Baca Langsung</span>
                        </label>
                        <label class="cursor-pointer border rounded-xl p-3 flex flex-col items-center text-center transition-all" :class="activityType === 'video' ? 'border-red-500 ring-2 ring-red-500 bg-red-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" x-model="activityType" value="video" class="sr-only">
                            <span class="p-1.5 rounded-lg bg-red-100 text-red-700 mb-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path></svg></span>
                            <span class="font-bold text-xs text-gray-900">Video Pembelajaran</span>
                            <span class="text-[10px] text-gray-500">YouTube Embed</span>
                        </label>
                        <label class="cursor-pointer border rounded-xl p-3 flex flex-col items-center text-center transition-all" :class="activityType === 'assignment' ? 'border-purple-500 ring-2 ring-purple-500 bg-purple-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" x-model="activityType" value="assignment" class="sr-only">
                            <span class="p-1.5 rounded-lg bg-purple-100 text-purple-700 mb-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg></span>
                            <span class="font-bold text-xs text-gray-900">Tugas / Assignment</span>
                            <span class="text-[10px] text-gray-500">Kumpul File/Teks</span>
                        </label>
                        <label class="cursor-pointer border rounded-xl p-3 flex flex-col items-center text-center transition-all" :class="activityType === 'attendance' ? 'border-emerald-500 ring-2 ring-emerald-500 bg-emerald-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" x-model="activityType" value="attendance" class="sr-only">
                            <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700 mb-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg></span>
                            <span class="font-bold text-xs text-gray-900">Presensi / Absensi</span>
                            <span class="text-[10px] text-gray-500">Kehadiran Siswa</span>
                        </label>
                    </div>
                </div>

                <!-- Formulir Material (File, Page, Video, Label) -->
                <form x-show="['file', 'page', 'video', 'label'].includes(activityType)" method="post" action="{{ route('materials.store', $course->id) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" x-model="activityType">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pilih / Ketik Topik (Pertemuan)</label>
                            <input list="topic_suggestions" id="activity_topic_select" name="topic" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Contoh: 01. Pengantar dan Konsep Dasar">
                            <datalist id="topic_suggestions">
                                @foreach($existingTopics ?? [] as $t)
                                <option value="{{ $t }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Aktivitas</label>
                            <input type="text" name="title" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Contoh: 1.1 Opening Pengantar Mata Kuliah">
                        </div>

                        <div x-show="activityType === 'video'">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Link URL Video YouTube</label>
                            <input type="url" name="video_url" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="https://www.youtube.com/watch?v=...">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1" x-text="activityType === 'label' ? 'Isi Teks / Salam Pembuka / Penutup' : (activityType === 'page' ? 'Isi Materi Lengkap' : 'Deskripsi / Instruksi')"></label>
                            <textarea name="content" rows="4" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Ketikkan teks materi atau instruksi pembelajaran..."></textarea>
                        </div>

                        <div x-show="activityType === 'file'">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Unggah Dokumen (PDF, PPTX, DOCX, ZIP)</label>
                            <input type="file" name="document" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-xs font-medium">Simpan Aktivitas</button>
                    </div>
                </form>

                <!-- Formulir Tugas / Assignment -->
                <form x-show="activityType === 'assignment'" method="post" action="{{ route('quizzes.store', $course->id) }}">
                    @csrf
                    <input type="hidden" name="type" value="assignment">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pilih / Ketik Topik (Pertemuan)</label>
                            <input list="topic_suggestions" name="topic" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Contoh: 01. Pengantar dan Konsep Dasar">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Tugas</label>
                            <input type="text" name="title" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Contoh: Tugas Mandiri Pertemuan 1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Instruksi Pengerjaan</label>
                            <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Jelaskan petunjuk pengumpulan dan kriteria tugas..."></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Waktu Mulai (Opsional)</label>
                                <input type="datetime-local" name="start_time" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Batas Waktu (Deadline)</label>
                                <input type="datetime-local" name="deadline" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-xs font-medium">Simpan Tugas</button>
                    </div>
                </form>

                <!-- Formulir Presensi (Absensi) -->
                <form x-show="activityType === 'attendance'" method="post" action="{{ route('attendances.store', $course->id) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pilih / Ketik Topik (Pertemuan)</label>
                            <input list="topic_suggestions" name="topic" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Contoh: 01. Pengantar dan Konsep Dasar">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Sesi Presensi</label>
                            <input type="text" name="title" required class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Contoh: Presensi Kehadiran Pertemuan 01">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Keterangan / Panduan (Opsional)</label>
                            <textarea name="description" rows="2" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Contoh: Silakan isi presensi kehadiran selama jam perkuliahan berlangsung."></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Waktu Mulai Presensi</label>
                                <input type="datetime-local" name="start_time" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Waktu Berakhir Presensi</label>
                                <input type="datetime-local" name="end_time" required value="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">Buat Sesi Presensi</button>
                    </div>
                </form>
            </div>
        </x-modal>

        <!-- Modal Edit Kelas -->
        <x-modal name="edit-course-modal" focusable>
            <form method="post" action="{{ route('kelas.update', $course->id) }}" class="p-6">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-bold text-gray-900 mb-4">Edit Informasi Kelas</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mata Pelajaran</label>
                        <input type="text" name="title" value="{{ $course->title }}" required class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                        <textarea name="description" rows="3" required class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20">{{ $course->description }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">Simpan Perubahan</button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Hapus Kelas -->
        <x-modal name="delete-course-modal" focusable>
            <form method="post" action="{{ route('kelas.destroy', $course->id) }}" class="p-6">
                @csrf
                @method('DELETE')
                <div class="flex items-center gap-3 text-red-600 mb-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">Konfirmasi Hapus Kelas</h2>
                </div>

                <p class="text-sm text-gray-600 mb-2">
                    Apakah Anda yakin ingin menghapus kelas <strong>"{{ $course->title }}"</strong>?
                </p>
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 mb-6">
                    <strong>Peringatan:</strong> Seluruh materi, kuis, penugasan, dan data nilai siswa yang ada di dalam kelas ini akan ikut terhapus secara permanen.
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Ya, Hapus Kelas</button>
                </div>
            </form>
        </x-modal>
        @endif
    </div>
</x-app-layout>

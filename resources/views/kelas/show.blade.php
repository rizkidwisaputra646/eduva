<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <x-breadcrumb :items="['Kelas & Mata Pelajaran' => route('kelas.index'), ($course->title ?? 'Detail Kelas') => '']" />
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ $course->title ?? 'Detail Kelas' }}
                </h2>
            </div>
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
                @elseif(Auth::user()->role === 'siswa')
                <button type="button" x-data @click="$dispatch('open-modal', 'leave-course-modal')" class="px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Keluar dari Kelas
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
            <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl border border-emerald-200 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-[#FDF0F0] text-[#C54B4B] p-4 rounded-xl border border-[#F8D5D5] text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-[#FDF0F0] text-[#C54B4B] p-4 rounded-xl border border-[#F8D5D5] text-sm space-y-1">
                <p class="font-semibold">Periksa kembali data yang Anda masukkan:</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Banner Kelas Fresh & Modern -->
        <div class="relative w-full h-48 md:h-64 rounded-xl md:rounded-2xl overflow-hidden shadow-xs mt-4 border border-[#E5EAF0]">
            <!-- Background Cover -->
            <div class="absolute inset-0 bg-gradient-to-r from-[#438FCE] to-[#5B68BE]"></div>
            <!-- Pattern Overlay -->
            <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>
            
            <div class="absolute bottom-0 left-0 p-6 md:p-8 w-full">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">{{ $course->title }}</h1>
                <p class="text-blue-50 text-base md:text-lg opacity-90">{{ $course->description ?? 'Deskripsi Mata Pelajaran' }}</p>
            </div>
            
            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
            <div class="absolute top-4 right-4 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-xl border border-white/30 text-white">
                <span class="text-xs font-medium opacity-80 block mb-0.5">Kode Kelas</span>
                <code class="font-mono font-bold text-lg select-all tracking-wider">{{ $course->invitation_code ?? '-' }}</code>
            </div>
            @endif
        </div>

        <!-- Tab Navigasi -->
        <div class="border-b border-[#E5EAF0]">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button @click="activeTab = 'stream'" 
                        :class="activeTab === 'stream' ? 'border-[#438FCE] text-[#438FCE] font-semibold' : 'border-transparent text-[#718096] hover:border-[#E5EAF0] hover:text-[#263445]'" 
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    Forum
                </button>
                <button @click="activeTab = 'classwork'" 
                        :class="activeTab === 'classwork' ? 'border-[#438FCE] text-[#438FCE] font-semibold' : 'border-transparent text-[#718096] hover:border-[#E5EAF0] hover:text-[#263445]'" 
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    Tugas Kelas
                </button>
                <button @click="activeTab = 'people'" 
                        :class="activeTab === 'people' ? 'border-[#438FCE] text-[#438FCE] font-semibold' : 'border-transparent text-[#718096] hover:border-[#E5EAF0] hover:text-[#263445]'" 
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
                    <div class="bg-white rounded-xl shadow-xs border border-[#E5EAF0] p-4">
                        <h3 class="font-bold text-[#263445] mb-2">Mendatang</h3>
                        @php
                            $upcoming = $course->quizzes->where('deadline', '>', now())->sortBy('deadline')->take(3);
                        @endphp
                        @forelse($upcoming as $task)
                            <div class="mb-3 last:mb-0">
                                <p class="text-sm font-medium text-[#263445] line-clamp-1">{{ $task->title }}</p>
                                <p class="text-xs text-[#B8873E] font-medium">{{ \Carbon\Carbon::parse($task->deadline)->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-[#718096] mb-3">Tidak ada tugas yang perlu segera diselesaikan.</p>
                        @endforelse
                        <a href="#" @click.prevent="activeTab = 'classwork'" class="text-xs font-semibold text-[#438FCE] hover:text-[#2E6C9E] block mt-2">Lihat semua &rarr;</a>
                    </div>
                </div>
                
                <!-- Main Stream -->
                <div class="md:col-span-3 space-y-4">
                    @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                    <!-- Pengumuman Input -->
                    <div x-data="{ expanded: false }" class="bg-white rounded-xl shadow-xs border border-[#E5EAF0] overflow-hidden transition-all">
                        <div x-show="!expanded" @click="expanded = true" class="p-4 flex items-center gap-4 cursor-text hover:bg-slate-50/50 transition-colors">
                            <div class="w-10 h-10 rounded-full bg-[#DFEAF9] flex items-center justify-center text-[#438FCE] font-bold">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="flex-1 text-[#718096] text-sm">
                                Umumkan sesuatu ke kelas Anda...
                            </div>
                        </div>
                        <div x-show="expanded" class="p-4" style="display: none;">
                            <form action="{{ route('announcements.store', $course->id) }}" method="POST">
                                @csrf
                                <textarea name="content" rows="4" class="w-full rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring focus:ring-[#438FCE]/20 text-[#263445] text-sm" placeholder="Umumkan sesuatu ke kelas Anda..." required></textarea>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" @click="expanded = false" class="px-4 py-2 text-[#718096] hover:bg-[#F5F8FC] rounded-lg text-sm font-medium">Batal</button>
                                    <button type="submit" class="px-4 py-2 bg-[#438FCE] text-white rounded-lg text-sm font-medium hover:bg-[#387EB8] transition-colors shadow-2xs">Posting</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Post List -->
                    @forelse($course->announcements as $announcement)
                    <div class="bg-white rounded-xl shadow-xs border border-[#E5EAF0] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-[#DFEAF9] flex items-center justify-center text-[#438FCE] font-bold">
                                    {{ substr($announcement->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-[#263445] text-sm">{{ $announcement->user->name }}</h4>
                                    <p class="text-xs text-[#718096]">{{ $announcement->created_at->format('d M Y, H:i') }}</p>
                                </div>
                            </div>

                            @if(Auth::user()->role === 'admin' || $announcement->user_id === Auth::id())
                            <div class="flex items-center gap-1">
                                <button x-data @click="$dispatch('open-modal', 'edit-announcement-{{ $announcement->id }}')" class="p-1.5 text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] rounded-lg transition-colors" title="Edit Postingan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button x-data @click="$dispatch('open-modal', 'delete-announcement-{{ $announcement->id }}')" class="p-1.5 text-[#718096] hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Postingan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>

                            <!-- Modal Edit Pengumuman -->
                            <x-modal name="edit-announcement-{{ $announcement->id }}" focusable>
                                <form method="post" action="{{ route('announcements.update', $announcement->id) }}" class="p-6">
                                    @csrf
                                    @method('PUT')
                                    <h2 class="text-base font-bold text-[#263445] mb-4">Edit Postingan Forum</h2>

                                    <div class="space-y-4">
                                        <textarea name="content" rows="4" required class="w-full rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring focus:ring-[#438FCE]/20 text-sm text-[#263445]">{{ $announcement->content }}</textarea>
                                    </div>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-[#E5EAF0] rounded-xl text-[#718096] hover:bg-[#F5F8FC] text-sm font-medium">Batal</button>
                                        <button type="submit" class="px-4 py-2 bg-[#438FCE] text-white rounded-xl hover:bg-[#387EB8] text-sm font-medium">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </x-modal>

                            <!-- Modal Hapus Pengumuman -->
                            <x-modal name="delete-announcement-{{ $announcement->id }}" focusable>
                                <form method="post" action="{{ route('announcements.destroy', $announcement->id) }}" class="p-6">
                                    @csrf
                                    @method('DELETE')
                                    <h2 class="text-base font-bold text-[#263445] mb-2">Hapus Postingan</h2>
                                    <p class="text-sm text-[#718096] mb-6">Apakah Anda yakin ingin menghapus postingan forum ini?</p>

                                    <div class="flex justify-end gap-3">
                                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-[#E5EAF0] rounded-xl text-[#718096] hover:bg-[#F5F8FC] text-sm font-medium">Batal</button>
                                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 text-sm font-medium">Ya, Hapus</button>
                                    </div>
                                </form>
                            </x-modal>
                            @endif
                        </div>
                        <div class="text-[#263445] text-sm whitespace-pre-wrap leading-relaxed">{{ $announcement->content }}</div>
                    </div>
                    @empty
                    <div class="bg-white rounded-xl shadow-xs border border-[#E5EAF0] p-6 text-center text-[#718096] text-sm">
                        Belum ada postingan di forum kelas ini.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        @php
            $topicIds = $groupedActivities->keys()->map(fn ($t) => (string) $t)->values();
        @endphp

        <!-- Tab: Tugas Kelas (Topic Outline / Alur Pembelajaran) -->
        <div
            x-show="activeTab === 'classwork'"
            x-data="topicAccordionManager({
                userId: {{ (int) Auth::id() }},
                courseId: {{ (int) $course->id }},
                topicIds: @js($topicIds)
            })"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-6 space-y-5"
        >
            
            <!-- Header Topic Outline: Clean & Minimalist -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 bg-white px-5 py-3.5 rounded-xl border border-[#E5EAF0] shadow-xs">
                <div>
                    <h3 class="font-bold text-[#263445] text-base">Alur Pembelajaran</h3>
                    <p class="text-xs text-[#718096]">Struktur materi, kuis, penugasan, dan presensi per pertemuan.</p>
                </div>
                
                <div class="flex items-center gap-2 flex-wrap">
                    @if($topicIds->isNotEmpty())
                    <!-- Single Toggle Buka Semua / Tutup Semua -->
                    <button
                        type="button"
                        @click="toggleAll()"
                        :aria-expanded="allOpen ? 'true' : 'false'"
                        :title="allOpen ? 'Tutup seluruh pertemuan' : 'Buka seluruh pertemuan'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-[#E5EAF0] bg-[#F5F8FC] text-[#263445] hover:text-[#438FCE] hover:border-[#438FCE]/50 hover:bg-white transition-all shadow-2xs inline-flex items-center gap-1.5"
                    >
                        <svg x-show="!allOpen" class="w-3.5 h-3.5 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <svg x-show="allOpen" x-cloak class="w-3.5 h-3.5 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                        <span x-text="allOpen ? 'Tutup Semua' : 'Buka Semua'">Buka Semua</span>
                    </button>
                    @endif

                    @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-add-activity', { meetingTopic: '', lockMeeting: false }); $dispatch('open-modal', 'add-activity')"
                        class="px-3.5 py-1.5 bg-[#438FCE] text-white rounded-lg font-semibold text-xs hover:bg-[#387EB8] transition-all shadow-xs flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Tambah Aktivitas</span>
                    </button>
                    @endif
                </div>
            </div>

            <!-- List Pertemuan (Topic Accordions) -->
            <div class="space-y-3.5 mb-8">
                @forelse($groupedActivities as $topic => $activities)
                <div data-topic-id="{{ (string) $topic }}" class="bg-white rounded-xl shadow-xs border border-[#E5EAF0] overflow-hidden transition-all">
                    <!-- Accordion Topic Header (Compact) -->
                    <div class="flex items-center justify-between bg-[#F8FAFD] px-4 py-2.5 border-b border-[#E5EAF0] hover:bg-[#F0F4FA] transition-colors">
                        <button
                            type="button"
                            @click="toggleTopic(topicIds[{{ $loop->index }}])"
                            :aria-expanded="isOpen(topicIds[{{ $loop->index }}]) ? 'true' : 'false'"
                            class="flex-1 text-left flex items-center gap-2.5 group focus:outline-none min-w-0"
                        >
                            <svg class="w-4 h-4 text-[#718096] group-hover:text-[#438FCE] transition-transform duration-200 shrink-0" :class="isOpen(topicIds[{{ $loop->index }}]) ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <h4 class="font-bold text-[#263445] text-sm group-hover:text-[#438FCE] transition-colors truncate">
                                {{ $topic }}
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#E5EAF0] text-[#718096] shrink-0">
                                {{ count($activities) }} aktivitas
                            </span>
                        </button>
                        
                        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                        <div class="flex items-center gap-1.5 shrink-0 ml-2">
                            <button
                                type="button"
                                x-data
                                @click="$dispatch('open-add-activity', { meetingTopic: @js((string) $topic), lockMeeting: true }); $dispatch('open-modal', 'add-activity')"
                                class="text-xs px-2.5 py-1 bg-white border border-[#E5EAF0] text-[#718096] hover:text-[#438FCE] hover:border-[#438FCE] rounded-lg transition-colors font-medium flex items-center gap-1"
                                title="Tambah aktivitas di pertemuan ini"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Tambah di Sini</span>
                            </button>
                            <button
                                type="button"
                                x-data
                                @click="$dispatch('open-delete-meeting', { topic: @js((string) $topic), activityCount: {{ count($activities) }} }); $dispatch('open-modal', 'delete-meeting-modal')"
                                class="p-1.5 bg-white border border-[#E5EAF0] text-[#718096] hover:text-[#C54B4B] hover:border-[#F8D5D5] hover:bg-[#FDF0F0] rounded-lg transition-colors"
                                title="Hapus pertemuan ini"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Accordion Content List -->
                    <div x-show="isOpen(topicIds[{{ $loop->index }}])" x-transition x-cloak class="divide-y divide-[#E5EAF0]">
                        @foreach($activities as $item)
                            @php
                                $isSubmitted = false;
                                if (Auth::user()->role === 'siswa') {
                                    if ($item->activity_type === 'assignment' && $item->submissions->isNotEmpty()) {
                                        $isSubmitted = true;
                                    } elseif ($item->activity_type === 'quiz' && $item->submissions->isNotEmpty()) {
                                        $isSubmitted = true;
                                    } elseif ($item->activity_type === 'attendance' && $item->logs->isNotEmpty()) {
                                        $isSubmitted = true;
                                    }
                                }
                                $hasDeadline = isset($item->deadline) && $item->deadline;
                                $isOverdue = false;
                                $isApproaching = false;
                                if ($hasDeadline) {
                                    $isOverdue = now() > $item->deadline;
                                    $hoursRemaining = now()->diffInHours($item->deadline, false);
                                    $isApproaching = !$isOverdue && $hoursRemaining <= 48;
                                }
                            @endphp
                            <div x-data="{ expanded: false, menuOpen: false }" class="border-b border-[#E5EAF0] last:border-b-0 hover:bg-[#F8FAFD]/60 transition-colors">
                                <!-- Main Compact Activity Row -->
                                <div class="px-3.5 sm:px-4 py-2.5 flex items-center justify-between gap-3">
                                    <!-- Left: Activity Icon & Primary Info -->
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <!-- Type Specific Icon -->
                                        @if($item->activity_type === 'material')
                                            @if($item->type === 'label')
                                                <div class="w-8 h-8 rounded-lg bg-[#FCEAD9] text-[#B8873E] border border-[#F8D5B5] flex items-center justify-center shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                                </div>
                                            @elseif($item->type === 'video')
                                                <div class="w-8 h-8 rounded-lg bg-[#FCEAD9] text-[#B8873E] border border-[#F8D5B5] flex items-center justify-center shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                </div>
                                            @elseif($item->type === 'page')
                                                <div class="w-8 h-8 rounded-lg bg-[#DDF1F0] text-[#419D98] border border-[#BDE3E1] flex items-center justify-center shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 rounded-lg bg-[#E0F0E6] text-[#32865B] border border-[#C4E4CF] flex items-center justify-center shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                </div>
                                            @endif
                                        @elseif($item->activity_type === 'quiz')
                                            <div class="w-8 h-8 rounded-lg bg-[#DFEAF9] text-[#438FCE] border border-[#C3DAF5] flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                            </div>
                                        @elseif($item->activity_type === 'assignment')
                                            <div class="w-8 h-8 rounded-lg bg-[#EEE5F8] text-[#895EB5] border border-[#DDCBEF] flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            </div>
                                        @elseif($item->activity_type === 'attendance')
                                            <div class="w-8 h-8 rounded-lg bg-[#DDF1F0] text-[#419D98] border border-[#BDE3E1] flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif

                                        <!-- Title & Metadata Badges -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span @click="expanded = !expanded" class="font-semibold text-[#263445] text-xs sm:text-sm hover:text-[#438FCE] transition-colors cursor-pointer truncate">
                                                    {{ $item->title }}
                                                </span>

                                                <!-- Small Type Label -->
                                                @if($item->activity_type === 'material')
                                                    @if($item->type === 'label')
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#FCEAD9] text-[#B8873E] border border-[#F8D5B5]">Pengantar</span>
                                                    @elseif($item->type === 'video')
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#FCEAD9] text-[#B8873E] border border-[#F8D5B5]">Video</span>
                                                    @elseif($item->type === 'page')
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#DDF1F0] text-[#419D98] border border-[#BDE3E1]">Halaman</span>
                                                    @else
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#E0F0E6] text-[#32865B] border border-[#C4E4CF]">Berkas</span>
                                                    @endif
                                                @elseif($item->activity_type === 'quiz')
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#DFEAF9] text-[#438FCE] border border-[#C3DAF5]">Kuis</span>
                                                @elseif($item->activity_type === 'assignment')
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#EEE5F8] text-[#895EB5] border border-[#DDCBEF]">Tugas</span>
                                                @elseif($item->activity_type === 'attendance')
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#DDF1F0] text-[#419D98] border border-[#BDE3E1]">Presensi</span>
                                                @endif

                                                <!-- Visibility Status (Teacher only) -->
                                                @if(!$item->is_visible)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-medium bg-[#F5F8FC] text-[#718096] border border-[#E5EAF0]">Draft / Tersembunyi</span>
                                                @endif
                                            </div>

                                            <!-- Secondary Context Info (Deadline, Format, Status) -->
                                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-[#718096] flex-wrap">
                                                @if($item->activity_type === 'material' && $item->type === 'file' && $item->file_path)
                                                    <span class="font-mono uppercase text-[10px] font-semibold text-[#718096]">{{ pathinfo($item->file_path, PATHINFO_EXTENSION) }}</span>
                                                @endif

                                                <!-- Dynamic Deadline Badge -->
                                                @if($hasDeadline)
                                                    @if($isSubmitted)
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-[#32865B] bg-[#E0F0E6] border border-[#C4E4CF] px-1.5 py-0.2 rounded">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Selesai
                                                        </span>
                                                    @elseif($isOverdue)
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-[#C54B4B] bg-[#FDF0F0] border border-[#F8D5D5] px-1.5 py-0.2 rounded">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            Terlewat ({{ $item->deadline->format('d M, H:i') }})
                                                        </span>
                                                    @elseif($isApproaching)
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-[#B8873E] bg-[#FDF5EB] border border-[#F7E1C8] px-1.5 py-0.2 rounded">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            Tenggat: {{ $item->deadline->diffForHumans() }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-[#718096] bg-slate-50 border border-[#E5EAF0] px-1.5 py-0.2 rounded">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                            {{ $item->deadline->format('d M, H:i') }}
                                                        </span>
                                                    @endif
                                                @endif

                                                @if($item->activity_type === 'quiz' && $item->detect_tab_switch)
                                                    <span>•</span>
                                                    <span class="text-[#B8873E] font-medium">Anti-Cheat Aktif</span>
                                                @endif

                                                @if($item->activity_type === 'quiz' && $item->duration)
                                                    <span>•</span>
                                                    <span class="text-[#438FCE] font-medium">{{ $item->formatted_duration }}</span>
                                                @endif

                                                @if($item->activity_type === 'attendance')
                                                    <span>•</span>
                                                    @if($item->is_active && now() >= $item->start_time && now() <= $item->end_time)
                                                        <span class="text-[#32865B] font-semibold">Sesi Terbuka</span>
                                                    @else
                                                        <span class="text-[#718096]">Sesi Ditutup</span>
                                                    @endif
                                                @endif

                                                <!-- Student Completion Status -->
                                                @if(Auth::user()->role === 'siswa' && !$hasDeadline)
                                                    @if($item->activity_type === 'assignment' && $item->submissions->isNotEmpty())
                                                        <span>•</span>
                                                        <span class="text-[#32865B] font-semibold">Sudah Mengumpulkan</span>
                                                    @elseif($item->activity_type === 'quiz' && $item->submissions->isNotEmpty())
                                                        <span>•</span>
                                                        <span class="text-[#32865B] font-semibold">Sudah Dikerjakan</span>
                                                    @elseif($item->activity_type === 'attendance' && $item->logs->isNotEmpty())
                                                        <span>•</span>
                                                        <span class="text-[#32865B] font-semibold capitalize">Status: {{ $item->logs->first()->status }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Actions & Ellipsis Menu -->
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <!-- Quick Primary Action for Student -->
                                        @if(Auth::user()->role === 'siswa')
                                            @if($item->activity_type === 'quiz' && $item->submissions->isEmpty())
                                                <a href="{{ url('kuis/'.$item->id.'/kerjakan') }}" class="text-xs px-2.5 py-1 bg-[#438FCE] text-white rounded-lg font-medium hover:bg-[#387EB8] transition-colors shadow-2xs">
                                                    Kerjakan
                                                </a>
                                            @elseif($item->activity_type === 'assignment' && $item->submissions->isEmpty())
                                                <button x-data @click="$dispatch('open-modal', 'submit-assignment-{{ $item->id }}')" class="text-xs px-2.5 py-1 bg-[#895EB5] text-white rounded-lg font-medium hover:bg-[#784FA3] transition-colors shadow-2xs">
                                                    Unggah
                                                </button>
                                            @elseif($item->activity_type === 'attendance' && $item->logs->isEmpty() && $item->is_active && now() >= $item->start_time && now() <= $item->end_time)
                                                <button x-data @click="$dispatch('open-modal', 'submit-attendance-{{ $item->id }}')" class="text-xs px-2.5 py-1 bg-[#419D98] text-white rounded-lg font-medium hover:bg-[#34827E] transition-colors shadow-2xs">
                                                    Presensi
                                                </button>
                                            @endif
                                        @endif

                                        <!-- Chevron Expand Toggle -->
                                        <button 
                                            @click="expanded = !expanded" 
                                            class="p-1.5 text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] rounded-lg transition-colors"
                                            :title="expanded ? 'Tutup Detail' : 'Buka Detail'"
                                        >
                                            <svg class="w-4 h-4 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <!-- 3-Dots Menu Dropdown -->
                                        <div class="relative" @click.outside="menuOpen = false">
                                            <button 
                                                @click.stop="menuOpen = !menuOpen" 
                                                class="p-1.5 text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] rounded-lg transition-colors"
                                                title="Pilihan Tindakan"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                                </svg>
                                            </button>

                                            <!-- Dropdown Menu Popover -->
                                            <div 
                                                x-show="menuOpen" 
                                                x-transition.opacity.duration.150ms 
                                                class="absolute right-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-[#E5EAF0] py-1 z-30 text-xs"
                                                x-cloak
                                            >
                                                <!-- Aksi Detail / Lihat -->
                                                <button @click="expanded = !expanded; menuOpen = false" class="w-full text-left px-3 py-1.5 text-[#263445] hover:bg-[#F5F8FC] flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-[#718096]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    <span x-text="expanded ? 'Tutup Detail' : 'Buka Detail'"></span>
                                                </button>

                                                <!-- Tanya AI (Khusus Siswa & Materi) -->
                                                @if(Auth::user()->role === 'siswa' && $item->activity_type === 'material')
                                                <a href="{{ route('ai-tutor.index', ['course_id' => $course->id, 'meeting_id' => $item->id]) }}" class="w-full text-left px-3 py-1.5 text-[#438FCE] hover:bg-[#DFEAF9]/40 flex items-center gap-2 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-[#438FCE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                    <span>Tanya AI Tutor</span>
                                                </a>
                                                @endif

                                                <!-- Unduh Dokumen (Jika Berkas) -->
                                                @if($item->activity_type === 'material' && $item->type === 'file' && $item->file_path)
                                                <a href="{{ Storage::url($item->file_path) }}" target="_blank" class="w-full text-left px-3 py-1.5 text-[#263445] hover:bg-[#F5F8FC] flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-[#718096]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    <span>Unduh Dokumen</span>
                                                </a>
                                                @endif

                                                <!-- Guru: Kelola Soal / Hasil / Pengumpulan -->
                                                @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                                    @if($item->activity_type === 'quiz')
                                                        <a href="{{ route('quizzes.show', $item->id) }}" class="w-full text-left px-3 py-1.5 text-[#438FCE] hover:bg-[#DFEAF9]/40 flex items-center gap-2 font-medium">
                                                            <svg class="w-3.5 h-3.5 text-[#438FCE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                            <span>Kelola Soal</span>
                                                        </a>
                                                        <a href="{{ url('assignments/'.$item->id.'/submissions') }}" class="w-full text-left px-3 py-1.5 text-[#5B68BE] hover:bg-[#DFEAF9]/40 flex items-center gap-2 font-medium">
                                                            <svg class="w-3.5 h-3.5 text-[#5B68BE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                                            <span>Hasil Ujian</span>
                                                        </a>
                                                    @elseif($item->activity_type === 'assignment')
                                                        <a href="{{ url('assignments/'.$item->id.'/submissions') }}" class="w-full text-left px-3 py-1.5 text-[#895EB5] hover:bg-[#EEE5F8]/40 flex items-center gap-2 font-medium">
                                                            <svg class="w-3.5 h-3.5 text-[#895EB5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                            <span>Lihat Pengumpulan</span>
                                                        </a>
                                                    @elseif($item->activity_type === 'attendance')
                                                        <a href="{{ route('attendances.report', $item->id) }}" class="w-full text-left px-3 py-1.5 text-[#419D98] hover:bg-[#DDF1F0]/40 flex items-center gap-2 font-medium">
                                                            <svg class="w-3.5 h-3.5 text-[#419D98]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                            <span>Rekap Kehadiran</span>
                                                        </a>
                                                        <form method="POST" action="{{ route('attendances.toggle-status', $item->id) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="w-full text-left px-3 py-1.5 text-[#263445] hover:bg-[#F5F8FC] flex items-center gap-2">
                                                                <svg class="w-3.5 h-3.5 text-[#718096]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                                                <span>{{ $item->is_active ? 'Tutup Sesi' : 'Buka Sesi' }}</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <div class="my-1 border-t border-[#E5EAF0]"></div>

                                                    <!-- Toggle Visibilitas -->
                                                    @if($item->activity_type === 'material')
                                                    <form method="POST" action="{{ route('materials.toggle', $item->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full text-left px-3 py-1.5 text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                            <span>{{ $item->is_visible ? 'Sembunyikan' : 'Tampilkan' }}</span>
                                                        </button>
                                                    </form>
                                                    @elseif($item->activity_type === 'quiz' || $item->activity_type === 'assignment')
                                                    <form method="POST" action="{{ route('quizzes.toggle', $item->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full text-left px-3 py-1.5 text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                            <span>{{ $item->is_visible ? 'Sembunyikan' : 'Tampilkan' }}</span>
                                                        </button>
                                                    </form>
                                                    @endif

                                                    <!-- Edit & Hapus -->
                                                    @if($item->activity_type === 'material')
                                                    <button @click="$dispatch('open-modal', 'edit-material-{{ $item->id }}'); menuOpen = false" class="w-full text-left px-3 py-1.5 text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                        <span>Edit</span>
                                                    </button>
                                                    <button @click="$dispatch('open-modal', 'delete-material-{{ $item->id }}'); menuOpen = false" class="w-full text-left px-3 py-1.5 text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        <span>Hapus</span>
                                                    </button>
                                                    @elseif($item->activity_type === 'quiz' || $item->activity_type === 'assignment')
                                                    <button @click="$dispatch('open-modal', 'edit-quiz-{{ $item->id }}'); menuOpen = false" class="w-full text-left px-3 py-1.5 text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                        <span>Edit</span>
                                                    </button>
                                                    <button @click="$dispatch('open-modal', 'delete-quiz-{{ $item->id }}'); menuOpen = false" class="w-full text-left px-3 py-1.5 text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        <span>Hapus</span>
                                                    </button>
                                                    @elseif($item->activity_type === 'attendance')
                                                    <button @click="$dispatch('open-modal', 'delete-attendance-{{ $item->id }}'); menuOpen = false" class="w-full text-left px-3 py-1.5 text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        <span>Hapus</span>
                                                    </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expandable Detail Section (Clean & Progressive Disclosure) -->
                                <div x-show="expanded" x-collapse class="px-4 py-3.5 bg-slate-50/70 border-t border-gray-100 text-xs text-gray-700 space-y-3">
                                    @if($item->activity_type === 'material')
                                        @if($item->type === 'label')
                                            <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line leading-relaxed">
                                                {{ $item->content }}
                                            </div>
                                        @elseif($item->type === 'video')
                                            @if($item->content)
                                                <p class="text-xs text-gray-600 leading-relaxed">{{ $item->content }}</p>
                                            @endif
                                            @if($item->video_url)
                                                @php
                                                    $videoUrl = $item->video_url;
                                                    $embedUrl = null;
                                                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $videoUrl, $matches)) {
                                                        $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $matches[1];
                                                    }
                                                @endphp
                                                @if($embedUrl)
                                                <div class="aspect-video max-w-lg rounded-xl overflow-hidden shadow-2xs border border-gray-200">
                                                    <iframe class="w-full h-full" src="{{ $embedUrl }}" frameborder="0" allowfullscreen></iframe>
                                                </div>
                                                @else
                                                <a href="{{ $videoUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 rounded-lg font-medium border border-rose-200 hover:bg-rose-100 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                    Buka Video Pembelajaran
                                                </a>
                                                @endif
                                            @endif
                                            @if(Auth::user()->role === 'siswa')
                                            <div class="pt-1 flex items-center gap-2">
                                                <a href="{{ route('ai-tutor.index', ['course_id' => $course->id, 'meeting_id' => $item->id]) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg bg-indigo-50 text-primary border border-indigo-100 hover:bg-primary hover:text-white transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                    Tanya AI Tutor
                                                </a>
                                            </div>
                                            @endif
                                        @elseif($item->type === 'page')
                                            <div class="prose prose-sm max-w-none text-gray-700 bg-white p-3.5 rounded-xl border border-gray-200 whitespace-pre-line leading-relaxed">
                                                {{ $item->content }}
                                            </div>
                                            @if(Auth::user()->role === 'siswa')
                                            <div class="pt-1 flex items-center gap-2">
                                                <a href="{{ route('ai-tutor.index', ['course_id' => $course->id, 'meeting_id' => $item->id]) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg bg-indigo-50 text-primary border border-indigo-100 hover:bg-primary hover:text-white transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                    Tanya AI Tutor
                                                </a>
                                            </div>
                                            @endif
                                        @else
                                            <!-- File Document -->
                                            @if($item->content)
                                                <p class="text-xs text-gray-600 leading-relaxed">{{ $item->content }}</p>
                                            @endif
                                            <div class="flex items-center gap-2.5 flex-wrap">
                                                @if($item->file_path)
                                                <a href="{{ Storage::url($item->file_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 rounded-lg text-xs font-semibold border border-emerald-200 hover:bg-emerald-100 transition-colors">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    Unduh Dokumen ({{ strtoupper(pathinfo($item->file_path, PATHINFO_EXTENSION)) }})
                                                </a>
                                                @endif
                                                @if(Auth::user()->role === 'siswa')
                                                <a href="{{ route('ai-tutor.index', ['course_id' => $course->id, 'meeting_id' => $item->id]) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg bg-indigo-50 text-primary border border-indigo-100 hover:bg-primary hover:text-white transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                    Tanya AI Tutor
                                                </a>
                                                @endif
                                            </div>
                                        @endif
                                    @elseif($item->activity_type === 'quiz')
                                        @if($item->description)
                                            <p class="text-xs text-gray-600 leading-relaxed">{{ $item->description }}</p>
                                        @endif
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-500">
                                            @if($item->start_time)
                                                <div>Waktu Mulai: <strong class="text-gray-800">{{ $item->start_time->format('d M Y, H:i') }} WIB</strong></div>
                                            @endif
                                            @if($item->deadline)
                                                <div>Batas Pengerjaan: <strong class="text-rose-600">{{ $item->deadline->format('d M Y, H:i') }} WIB</strong></div>
                                            @endif
                                        </div>
                                        @if($item->detect_tab_switch)
                                            <div class="p-2.5 bg-amber-50/70 border border-amber-200 rounded-lg text-[11px] text-amber-800 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                <span>Ujian ini dilengkapi pengawasan pindah tab otomatis.</span>
                                            </div>
                                        @endif
                                        <div class="pt-1 flex items-center gap-2">
                                            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                                <a href="{{ route('quizzes.show', $item->id) }}" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg font-medium text-xs hover:bg-blue-700 transition-colors">Kelola Soal</a>
                                                <a href="{{ url('assignments/'.$item->id.'/submissions') }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 border border-gray-200 rounded-lg font-medium text-xs hover:bg-gray-200 transition-colors">Hasil Ujian</a>
                                            @else
                                                @if($item->submissions->isNotEmpty())
                                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold">Ujian Telah Diselesaikan</span>
                                                @else
                                                    <a href="{{ url('kuis/'.$item->id.'/kerjakan') }}" class="px-3.5 py-1.5 bg-blue-600 text-white rounded-lg font-medium text-xs hover:bg-blue-700 transition-colors">Kerjakan Kuis Sekarang</a>
                                                @endif
                                            @endif
                                        </div>
                                    @elseif($item->activity_type === 'assignment')
                                        @if($item->description)
                                            <p class="text-xs text-gray-600 leading-relaxed">{{ $item->description }}</p>
                                        @endif
                                        <div class="text-xs text-gray-500">
                                            @if($item->deadline)
                                                <span>Tenggat Pengumpulan: <strong class="text-rose-600">{{ $item->deadline->format('d M Y, H:i') }} WIB</strong></span>
                                            @endif
                                        </div>
                                        <div class="pt-1 flex items-center gap-2">
                                            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                                <a href="{{ url('assignments/'.$item->id.'/submissions') }}" class="px-3 py-1.5 bg-purple-600 text-white rounded-lg font-medium text-xs hover:bg-purple-700 transition-colors">Lihat Pengumpulan Siswa</a>
                                            @else
                                                @if($item->submissions->isNotEmpty())
                                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold">Tugas Sudah Dikumpulkan</span>
                                                @else
                                                    <button @click="$dispatch('open-modal', 'submit-assignment-{{ $item->id }}')" class="px-3.5 py-1.5 bg-purple-600 text-white rounded-lg font-medium text-xs hover:bg-purple-700 transition-colors">Unggah Berkas Tugas</button>
                                                @endif
                                            @endif
                                        </div>
                                    @elseif($item->activity_type === 'attendance')
                                        @if($item->description)
                                            <p class="text-xs text-gray-600 leading-relaxed">{{ $item->description }}</p>
                                        @endif
                                        <div class="text-xs text-gray-500">
                                            Periode: <strong class="text-gray-800">{{ $item->start_time->format('d M Y, H:i') }} - {{ $item->end_time->format('H:i') }} WIB</strong>
                                        </div>
                                        <div class="pt-1 flex items-center gap-2">
                                            @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                                <a href="{{ route('attendances.report', $item->id) }}" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg font-medium text-xs hover:bg-emerald-700 transition-colors">Rekap Kehadiran</a>
                                            @else
                                                @php $studentAtt = $item->logs->first(); @endphp
                                                @if($studentAtt)
                                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold capitalize">Tercatat: {{ $studentAtt->status }}</span>
                                                @else
                                                    @if($item->is_active && now() >= $item->start_time && now() <= $item->end_time)
                                                        <button @click="$dispatch('open-modal', 'submit-attendance-{{ $item->id }}')" class="px-3.5 py-1.5 bg-emerald-600 text-white rounded-lg font-medium text-xs hover:bg-emerald-700 transition-colors">Isi Presensi Sekarang</button>
                                                    @else
                                                        <span class="text-xs text-gray-400 italic">Sesi presensi sedang ditutup.</span>
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Modals (Edit, Delete, Submit per item) -->
                                @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
                                    @if($item->activity_type === 'material')
                                    <x-modal name="edit-material-{{ $item->id }}" focusable>
                                        <form method="post" action="{{ route('materials.update', $item->id) }}" enctype="multipart/form-data" class="p-6">
                                            @csrf
                                            @method('PUT')
                                            <h2 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Edit Aktivitas ({{ ucfirst($item->type) }})</h2>
                                            <div class="space-y-4 text-xs">
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Topik / Pertemuan</label>
                                                    <input type="text" name="topic" value="{{ $item->topic }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Judul</label>
                                                    <input type="text" name="title" value="{{ $item->title }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">
                                                </div>
                                                @if($item->type === 'video')
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">URL Video (YouTube)</label>
                                                    <input type="url" name="video_url" value="{{ $item->video_url }}" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">
                                                </div>
                                                @endif
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Konten / Instruksi</label>
                                                    <textarea name="content" rows="4" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">{{ $item->content }}</textarea>
                                                </div>
                                                @if($item->type === 'file')
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Ganti Berkas (Opsional)</label>
                                                    <input type="file" name="document" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-primary">
                                                </div>
                                                @endif
                                            </div>
                                            <div class="mt-6 flex justify-end gap-2.5 pt-3 border-t border-gray-100 text-xs">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-3.5 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                                                <button type="submit" class="px-4 py-1.5 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </x-modal>

                                    <x-modal name="delete-material-{{ $item->id }}" focusable>
                                        <form method="post" action="{{ route('materials.destroy', $item->id) }}" class="p-5">
                                            @csrf
                                            @method('DELETE')
                                            <h2 class="text-base font-bold text-gray-900 mb-1.5">Hapus Aktivitas</h2>
                                            <p class="text-xs text-gray-600 mb-5">Apakah Anda yakin ingin menghapus <strong>"{{ $item->title }}"</strong>?</p>
                                            <div class="flex justify-end gap-2 text-xs">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                                                <button type="submit" class="px-3.5 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Hapus</button>
                                            </div>
                                        </form>
                                    </x-modal>
                                    @elseif($item->activity_type === 'quiz' || $item->activity_type === 'assignment')
                                    @php
                                        $initialHours = $item->duration ? floor($item->duration / 60) : 0;
                                        $initialMinutes = $item->duration ? ($item->duration % 60) : 0;
                                        $hasActiveSession = $item->activity_type === 'quiz' && $item->examSessions()->where('status', 'in_progress')->exists();
                                    @endphp
                                    <x-modal name="edit-quiz-{{ $item->id }}" focusable>
                                        <form method="post" action="{{ route('quizzes.update', $item->id) }}" class="p-6" x-data="{
                                            hours: {{ $initialHours }},
                                            minutes: {{ $initialMinutes }},
                                            locked: {{ $hasActiveSession ? 'true' : 'false' }},
                                            get totalMinutes() {
                                                return (parseInt(this.hours) || 0) * 60 + (parseInt(this.minutes) || 0);
                                            },
                                            get durationLabel() {
                                                const h = parseInt(this.hours) || 0;
                                                const m = parseInt(this.minutes) || 0;
                                                if (h === 0 && m === 0) return 'Tanpa Batasan Durasi';
                                                let parts = [];
                                                if (h > 0) parts.push(h + ' Jam');
                                                if (m > 0) parts.push(m + ' Menit');
                                                return parts.join(' ') + ' (' + this.totalMinutes + ' Menit Total)';
                                            },
                                            setPreset(h, m) {
                                                if (!this.locked) {
                                                    this.hours = h;
                                                    this.minutes = m;
                                                }
                                            }
                                        }">
                                            @csrf
                                            @method('PUT')
                                            <h2 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Edit {{ $item->activity_type === 'quiz' ? 'Kuis' : 'Tugas' }}</h2>
                                            <div class="space-y-4 text-xs">
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Topik / Pertemuan</label>
                                                    <input type="text" name="topic" value="{{ $item->topic }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Judul</label>
                                                    <input type="text" name="title" value="{{ $item->title }}" required class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Instruksi / Deskripsi</label>
                                                    <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">{{ $item->description }}</textarea>
                                                </div>

                                                @if($item->activity_type === 'quiz')
                                                <!-- Pengaturan Durasi Ujian (Jam & Menit) -->
                                                <div class="p-3.5 bg-blue-50/50 rounded-xl border border-blue-200/80 space-y-2.5">
                                                    <div class="flex items-center justify-between">
                                                        <label class="block font-bold text-gray-900 text-xs">Durasi Pengerjaan Ujian</label>
                                                        <span class="text-[11px] font-semibold text-primary" x-text="durationLabel"></span>
                                                    </div>

                                                    @if($hasActiveSession)
                                                    <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-200 text-[11px] text-amber-800 flex items-center gap-2">
                                                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                        <span>Durasi dikunci karena terdapat siswa yang sedang aktif mengerjakan ujian ini.</span>
                                                    </div>
                                                    @endif

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Jam</label>
                                                            <div class="relative">
                                                                <input type="number" name="duration_hours" min="0" max="24" x-model="hours" :disabled="locked" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs pr-10 font-bold disabled:bg-gray-100 disabled:text-gray-400" placeholder="0">
                                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[11px]">Jam</span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Menit</label>
                                                            <div class="relative">
                                                                <input type="number" name="duration_minutes" min="0" max="59" step="5" x-model="minutes" :disabled="locked" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs pr-12 font-bold disabled:bg-gray-100 disabled:text-gray-400" placeholder="0">
                                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[11px]">Menit</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if(!$hasActiveSession)
                                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                                        <span class="text-[10px] text-gray-400 font-medium mr-1">Preset:</span>
                                                        <button type="button" @click="setPreset(0, 30)" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white border border-gray-200 hover:border-primary hover:text-primary transition-colors">30 Menit</button>
                                                        <button type="button" @click="setPreset(0, 45)" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white border border-gray-200 hover:border-primary hover:text-primary transition-colors">45 Menit</button>
                                                        <button type="button" @click="setPreset(1, 0)" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white border border-gray-200 hover:border-primary hover:text-primary transition-colors">1 Jam</button>
                                                        <button type="button" @click="setPreset(1, 30)" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white border border-gray-200 hover:border-primary hover:text-primary transition-colors">1.5 Jam</button>
                                                        <button type="button" @click="setPreset(2, 0)" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white border border-gray-200 hover:border-primary hover:text-primary transition-colors">2 Jam</button>
                                                        <button type="button" @click="setPreset(0, 0)" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white border border-gray-200 hover:text-gray-900 transition-colors">Tanpa Batas</button>
                                                    </div>
                                                    @endif
                                                </div>
                                                @endif

                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Tenggat Waktu</label>
                                                    <input type="datetime-local" name="deadline" value="{{ $item->deadline ? $item->deadline->format('Y-m-d\TH:i') : '' }}" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs">
                                                </div>
                                                @if($item->activity_type === 'quiz')
                                                <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200">
                                                    <label class="flex items-start gap-2.5 cursor-pointer">
                                                        <input type="checkbox" name="detect_tab_switch" value="1" {{ $item->detect_tab_switch ? 'checked' : '' }} class="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary h-4 w-4">
                                                        <div>
                                                            <span class="block font-bold text-gray-900 text-xs">Deteksi Pindah Tab (Anti-Cheating)</span>
                                                            <span class="block text-[11px] text-gray-600 mt-0.5 leading-relaxed">Catat riwayat dan waktu siswa berpindah tab browser saat ujian.</span>
                                                        </div>
                                                    </label>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="mt-6 flex justify-end gap-2.5 pt-3 border-t border-gray-100 text-xs">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-3.5 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                                                <button type="submit" class="px-4 py-1.5 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </x-modal>

                                    <x-modal name="delete-quiz-{{ $item->id }}" focusable>
                                        <form method="post" action="{{ route('quizzes.destroy', $item->id) }}" class="p-5">
                                            @csrf
                                            @method('DELETE')
                                            <h2 class="text-base font-bold text-gray-900 mb-1.5">Hapus Aktivitas</h2>
                                            <p class="text-xs text-gray-600 mb-5">Apakah Anda yakin ingin menghapus <strong>"{{ $item->title }}"</strong>?</p>
                                            <div class="flex justify-end gap-2 text-xs">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                                                <button type="submit" class="px-3.5 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Hapus</button>
                                            </div>
                                        </form>
                                    </x-modal>
                                    @elseif($item->activity_type === 'attendance')
                                    <x-modal name="delete-attendance-{{ $item->id }}" focusable>
                                        <form method="post" action="{{ route('attendances.destroy', $item->id) }}" class="p-5">
                                            @csrf
                                            @method('DELETE')
                                            <h2 class="text-base font-bold text-gray-900 mb-1.5">Hapus Sesi Presensi</h2>
                                            <p class="text-xs text-gray-600 mb-5">Apakah Anda yakin ingin menghapus presensi <strong>"{{ $item->title }}"</strong>?</p>
                                            <div class="flex justify-end gap-2 text-xs">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                                                <button type="submit" class="px-3.5 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Hapus</button>
                                            </div>
                                        </form>
                                    </x-modal>
                                    @endif
                                @endif

                                @if(Auth::user()->role === 'siswa')
                                    @if($item->activity_type === 'assignment')
                                    <x-modal name="submit-assignment-{{ $item->id }}" focusable>
                                        <form method="post" action="{{ url('assignments/'.$item->id.'/submit') }}" enctype="multipart/form-data" class="p-5">
                                            @csrf
                                            <h2 class="text-base font-bold text-gray-900 mb-1.5">Unggah Tugas: {{ $item->title }}</h2>
                                            <p class="text-xs text-gray-500 mb-4">{{ $item->description }}</p>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Berkas Tugas (PDF, DOCX, ZIP)</label>
                                                <input type="file" name="submission_file" required accept=".pdf,.doc,.docx,.zip,.rar" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700">
                                            </div>
                                            <div class="mt-6 flex justify-end gap-2 text-xs">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                                                <button type="submit" class="px-4 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">Kumpulkan</button>
                                            </div>
                                        </form>
                                    </x-modal>
                                    @elseif($item->activity_type === 'attendance')
                                    <x-modal name="submit-attendance-{{ $item->id }}" focusable>
                                        <form method="post" action="{{ route('attendances.submit', $item->id) }}" class="p-5">
                                            @csrf
                                            <h2 class="text-base font-bold text-gray-900 mb-1">Presensi Kehadiran: {{ $item->title }}</h2>
                                            <p class="text-xs text-gray-500 mb-4">{{ $item->topic }} • Waktu: {{ now()->format('H:i') }} WIB</p>
                                            <div class="space-y-3.5 text-xs">
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-2">Status Kehadiran</label>
                                                    <div class="grid grid-cols-3 gap-2">
                                                        <label class="cursor-pointer border rounded-xl p-2 text-center hover:bg-emerald-50 border-gray-200 flex flex-col items-center">
                                                            <input type="radio" name="status" value="hadir" checked class="text-primary mb-1">
                                                            <span class="font-bold text-gray-800">Hadir</span>
                                                        </label>
                                                        <label class="cursor-pointer border rounded-xl p-2 text-center hover:bg-blue-50 border-gray-200 flex flex-col items-center">
                                                            <input type="radio" name="status" value="izin" class="text-primary mb-1">
                                                            <span class="font-bold text-gray-800">Izin</span>
                                                        </label>
                                                        <label class="cursor-pointer border rounded-xl p-2 text-center hover:bg-amber-50 border-gray-200 flex flex-col items-center">
                                                            <input type="radio" name="status" value="sakit" class="text-primary mb-1">
                                                            <span class="font-bold text-gray-800">Sakit</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-gray-700 mb-1">Catatan (Opsional)</label>
                                                    <input type="text" name="note" class="w-full rounded-lg border-gray-300 focus:border-primary text-xs" placeholder="Keterangan kehadiran...">
                                                </div>
                                            </div>
                                            <div class="mt-5 flex justify-end gap-2 text-xs pt-3 border-t border-gray-100">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                                                <button type="submit" class="px-4 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">Kirim Presensi</button>
                                            </div>
                                        </form>
                                    </x-modal>
                                    @endif
                                @endif
                            </div>
                        @endforeach

                        <!-- Forum Diskusi Terintegrasi pada Topik -->
                        <div class="px-4 py-2.5 bg-slate-50/50 hover:bg-slate-100/60 transition-colors flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200/60 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                                </span>
                                <div class="truncate">
                                    <span class="font-bold text-gray-800">Forum Diskusi: {{ $topic }}</span>
                                    <span class="text-gray-400 hidden sm:inline">• Ruang tanya jawab kelas</span>
                                </div>
                            </div>
                            <button @click="activeTab = 'stream'" class="px-2.5 py-1 bg-white border border-gray-200 text-primary hover:bg-indigo-50 hover:border-primary rounded-lg font-medium transition-colors shrink-0">
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

        <!-- Modal Tambah Aktivitas Unified (Modern Professional Redesign) -->
        @if(Auth::user()->role === 'guru' || Auth::user()->role === 'admin')
        <x-modal name="add-activity" maxWidth="2xl" focusable>
            <form
                x-data="addActivityModal({
                    courseId: {{ (int) $course->id }},
                    courseTitle: @js($course->title),
                    meetings: @js($courseMeetings ?? []),
                    routes: {
                        materials: @js(route('materials.store', $course->id)),
                        quizzes: @js(route('quizzes.store', $course->id)),
                        attendances: @js(route('attendances.store', $course->id)),
                    },
                    initialStartDate: @js(now()->format('Y-m-d')),
                    initialStartTime: @js(now()->format('H:i')),
                    initialEndDate: @js(now()->addHours(2)->format('Y-m-d')),
                    initialEndTime: @js(now()->addHours(2)->format('H:i')),
                })"
                x-on:open-add-activity.window="openWithContext($event.detail)"
                @submit="handleSubmit($event)"
                method="post"
                :action="formAction"
                enctype="multipart/form-data"
                novalidate
                class="flex flex-col max-h-[88vh] sm:max-h-[86vh] bg-white overflow-hidden"
            >
                @csrf
                <input type="hidden" name="type" :disabled="activityType === 'attendance'" :value="activityType">
                <input type="hidden" name="topic" :value="selectedTopic">
                <input type="hidden" name="meeting_id" :value="selectedTopic">

                <!-- A. Modal Header -->
                <div class="px-5 sm:px-6 py-4 border-b border-[#E5EAF0] flex items-start justify-between gap-4 shrink-0 bg-white">
                    <div class="min-w-0">
                        <h2 class="text-base sm:text-lg font-bold text-[#263445] tracking-tight">Tambah Aktivitas</h2>
                        <p class="text-xs sm:text-[13px] text-[#718096] mt-0.5 leading-snug">
                            Pilih jenis aktivitas pembelajaran untuk ditambahkan ke pertemuan ini.
                        </p>
                    </div>
                    <button
                        type="button"
                        x-on:click="$dispatch('close')"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] transition-colors shrink-0"
                        title="Tutup"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- B. Modal Body (Scrollable) -->
                <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5">
                    <!-- 1. Jenis Aktivitas (Modern Custom Dropdown) -->
                    <div class="relative" @click.outside="typeDropdownOpen = false">
                        <label class="block text-xs font-semibold text-[#263445] mb-1.5">
                            Jenis Aktivitas <span class="text-[#C54B4B]">*</span>
                        </label>

                        <button
                            type="button"
                            @click="typeDropdownOpen = !typeDropdownOpen; meetingDropdownOpen = false"
                            class="w-full min-h-[48px] flex items-center justify-between gap-3 px-3.5 py-2.5 bg-[#FAFBFD] hover:bg-white border rounded-[11px] text-left transition-all outline-none"
                            :class="typeDropdownOpen ? 'bg-white border-[#438FCE] ring-2 ring-[#438FCE]/15' : 'border-[#D7DFE9] hover:border-[#438FCE]/60'"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="currentType.badgeClass"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="currentType.iconPath"></path>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <span class="font-semibold text-[#263445] text-sm block leading-tight truncate" x-text="currentType.name"></span>
                                    <span class="text-xs text-[#718096] block leading-tight mt-0.5 truncate" x-text="currentType.desc"></span>
                                </div>
                            </div>
                            <svg
                                class="w-4 h-4 text-[#718096] shrink-0 transition-transform duration-200"
                                :class="typeDropdownOpen ? 'rotate-180 text-[#438FCE]' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Popover List Jenis Aktivitas -->
                        <div
                            x-show="typeDropdownOpen"
                            x-transition.opacity.duration.120ms
                            x-cloak
                            class="absolute left-0 right-0 mt-1.5 bg-white rounded-xl shadow-xl border border-[#E5EAF0] p-1.5 space-y-0.5 z-30 max-h-72 overflow-y-auto"
                        >
                            <template x-for="t in types" :key="t.id">
                                <button
                                    type="button"
                                    @click="selectType(t.id)"
                                    class="w-full flex items-center justify-between gap-3 px-2.5 py-2 rounded-lg text-left transition-colors"
                                    :class="activityType === t.id ? 'bg-[#DFEAF9]/60 text-[#2E6C9E]' : 'hover:bg-[#F5F8FC] text-[#263445]'"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" :class="t.badgeClass">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="t.iconPath"></path>
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <span class="font-semibold text-[13px] block leading-tight truncate" x-text="t.name"></span>
                                            <span class="text-[11px] text-[#718096] block leading-tight mt-0.5 truncate" x-text="t.desc"></span>
                                        </div>
                                    </div>
                                    <svg x-show="activityType === t.id" class="w-4 h-4 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 2. Pilih Pertemuan (Searchable Dropdown dari Database / Terkunci Otomatis jika dibuka dari pertemuan) -->
                    <div class="relative" @click.outside="meetingDropdownOpen = false">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <label class="block text-xs font-semibold text-[#263445]">
                                Pilih Pertemuan <span class="text-[#C54B4B]">*</span>
                            </label>
                            <span class="text-[11px] text-[#718096] truncate max-w-[60%]" x-text="courseTitle"></span>
                        </div>

                        <!-- Kondisi A: Dibuka langsung dari pertemuan spesifik ("Tambah di Sini") -->
                        <template x-if="lockMeeting">
                            <div class="w-full min-h-[46px] px-3.5 py-2.5 bg-[#F5F8FC] border border-[#D7DFE9] rounded-[11px] flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-7 h-7 rounded-lg bg-[#DFEAF9] text-[#2E6C9E] flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <span class="text-sm font-semibold text-[#263445] block leading-tight truncate" x-text="selectedMeetingObj ? selectedMeetingObj.display_label : selectedTopic"></span>
                                        <span class="text-[11px] text-[#718096] block leading-tight mt-0.5 truncate" x-text="courseTitle + ' • Pertemuan aktif dipilih otomatis'"></span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-md bg-[#DFEAF9] text-[#2E6C9E] text-[11px] font-semibold shrink-0">
                                    Otomatis
                                </span>
                            </div>
                        </template>

                        <!-- Kondisi B: Dibuka dari halaman pengelolaan kelas (Searchable Dropdown) -->
                        <template x-if="!lockMeeting">
                            <div>
                                <button
                                    type="button"
                                    @click="toggleMeetingDropdown()"
                                    class="w-full min-h-[46px] flex items-center justify-between gap-3 px-3.5 py-2.5 bg-[#FAFBFD] hover:bg-white border rounded-[11px] text-left transition-all outline-none"
                                    :class="errors.topic
                                        ? 'border-[#C54B4B] bg-[#FDF0F0]/50 focus:border-[#C54B4B] focus:ring-2 focus:ring-[#C54B4B]/15'
                                        : (meetingDropdownOpen ? 'bg-white border-[#438FCE] ring-2 ring-[#438FCE]/15' : 'border-[#D7DFE9] hover:border-[#438FCE]/60')"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span
                                            class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold shrink-0"
                                            :class="selectedTopic ? 'bg-[#DFEAF9] text-[#2E6C9E]' : 'bg-[#EEF2F7] text-[#718096]'"
                                            x-text="selectedMeetingObj && selectedMeetingObj.meeting_number ? selectedMeetingObj.meeting_number : '#'"
                                        ></span>
                                        <div class="min-w-0">
                                            <template x-if="selectedTopic">
                                                <div>
                                                    <span class="text-sm font-semibold text-[#263445] block leading-tight truncate" x-text="selectedMeetingObj ? selectedMeetingObj.display_label : selectedTopic"></span>
                                                    <span class="text-[11px] text-[#718096] block leading-tight mt-0.5 truncate" x-text="selectedMeetingSubtitle"></span>
                                                </div>
                                            </template>
                                            <template x-if="!selectedTopic">
                                                <span class="text-sm text-[#94A3B8] block truncate">Pilih pertemuan kelas atau buat pertemuan baru...</span>
                                            </template>
                                        </div>
                                    </div>
                                    <svg
                                        class="w-4 h-4 text-[#718096] shrink-0 transition-transform duration-200"
                                        :class="meetingDropdownOpen ? 'rotate-180 text-[#438FCE]' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Dropdown Search & List Pertemuan -->
                                <div
                                    x-show="meetingDropdownOpen"
                                    x-transition.opacity.duration.120ms
                                    x-cloak
                                    class="absolute left-0 right-0 mt-1.5 bg-white rounded-xl shadow-xl border border-[#E5EAF0] overflow-hidden z-30"
                                >
                                    <div class="p-2 border-b border-[#E5EAF0] bg-[#FAFBFD]">
                                        <div class="relative">
                                            <svg class="w-4 h-4 text-[#94A3B8] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                            <input
                                                x-ref="meetingSearchInput"
                                                type="text"
                                                x-model="meetingSearch"
                                                @keydown.enter.prevent="selectFirstOrNewMeeting()"
                                                placeholder="Cari nomor/judul pertemuan atau ketik nama baru..."
                                                class="w-full min-h-[38px] pl-9 pr-3 py-1.5 bg-white border border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15 rounded-lg text-xs text-[#263445] placeholder:text-[#94A3B8] outline-none"
                                            >
                                        </div>
                                    </div>

                                    <div class="max-h-56 overflow-y-auto p-1.5 space-y-0.5">
                                        <template x-for="m in filteredMeetings" :key="m.id">
                                            <button
                                                type="button"
                                                @click="selectMeeting(m.topic)"
                                                class="w-full flex items-center justify-between gap-2.5 px-2.5 py-2 rounded-lg text-left transition-colors"
                                                :class="selectedTopic === m.topic ? 'bg-[#DFEAF9]/60 text-[#2E6C9E]' : 'hover:bg-[#F5F8FC] text-[#263445]'"
                                            >
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <span
                                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold shrink-0"
                                                        :class="selectedTopic === m.topic ? 'bg-[#438FCE] text-white' : 'bg-[#EEF2F7] text-[#263445]'"
                                                        x-text="m.meeting_number"
                                                    ></span>
                                                    <div class="min-w-0">
                                                        <span class="text-[13px] font-semibold block leading-tight truncate" x-text="m.display_label"></span>
                                                        <span class="text-[11px] text-[#718096] block leading-tight mt-0.5 truncate" x-text="m.course_title + ' • ' + m.activity_count + ' aktivitas'"></span>
                                                    </div>
                                                </div>
                                                <svg x-show="selectedTopic === m.topic" class="w-4 h-4 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </template>

                                        <!-- Empty State saat kelas belum punya pertemuan -->
                                        <div x-show="filteredMeetings.length === 0 && !canCreateNewTopicFromSearch" class="px-3 py-4 text-center text-xs text-[#718096]">
                                            <span x-show="meetings.length === 0">Belum ada pertemuan yang dibuat pada kelas ini. Ketik nama pertemuan baru di kolom pencarian (contoh: <strong>Pertemuan 01 — Pengantar</strong>).</span>
                                            <span x-show="meetings.length > 0">Tidak ada pertemuan yang cocok dengan pencarian Anda.</span>
                                        </div>

                                        <!-- Opsi Tambah Pertemuan Baru dari Kata Kunci Pencarian -->
                                        <button
                                            x-show="canCreateNewTopicFromSearch"
                                            type="button"
                                            @click="selectMeeting(meetingSearch.trim())"
                                            class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-left bg-[#F5F8FC] hover:bg-[#DFEAF9]/70 text-[#2E6C9E] border border-dashed border-[#438FCE]/40 transition-colors mt-1"
                                        >
                                            <span class="w-7 h-7 rounded-lg bg-[#438FCE] text-white flex items-center justify-center shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            </span>
                                            <div class="min-w-0">
                                                <span class="text-xs font-semibold block truncate">Buat pertemuan baru: "<span x-text="meetingSearch.trim()"></span>"</span>
                                                <span class="text-[11px] text-[#718096] block truncate" x-text="'Akan ditambahkan ke kelas ' + courseTitle"></span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <p x-show="errors.topic" x-cloak class="mt-1.5 text-xs text-[#C54B4B] font-medium" x-text="errors.topic"></p>
                    </div>

                    <!-- 3. Judul Aktivitas / Sesi -->
                    <div>
                        <label for="add_activity_title" class="block text-xs font-semibold text-[#263445] mb-1.5">
                            <span x-text="titleFieldLabel"></span> <span class="text-[#C54B4B]">*</span>
                        </label>
                        <input
                            id="add_activity_title"
                            type="text"
                            name="title"
                            x-model="title"
                            @input="if (title.trim()) errors.title = ''"
                            :placeholder="titlePlaceholder"
                            class="w-full min-h-[46px] px-3.5 py-2.5 bg-[#FAFBFD] hover:bg-white focus:bg-white border rounded-[11px] text-sm text-[#263445] placeholder:text-[#94A3B8] transition-all outline-none"
                            :class="errors.title ? 'border-[#C54B4B] bg-[#FDF0F0]/50 focus:border-[#C54B4B] focus:ring-2 focus:ring-[#C54B4B]/15' : 'border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15'"
                        >
                        <p x-show="errors.title" x-cloak class="mt-1.5 text-xs text-[#C54B4B] font-medium" x-text="errors.title"></p>
                    </div>

                    <!-- 4. Panduan / Keterangan (Opsional) -->
                    <div>
                        <label for="add_activity_notes" class="block text-xs font-semibold text-[#263445] mb-1.5">
                            <span x-text="notesFieldLabel"></span>
                            <span class="font-normal text-[#718096]" x-show="!['label', 'page'].includes(activityType)">(Opsional)</span>
                        </label>
                        <textarea
                            id="add_activity_notes"
                            x-ref="notesTextarea"
                            x-model="notes"
                            :name="isMaterialType ? 'content' : 'description'"
                            @input="autoResizeTextarea($el)"
                            rows="4"
                            placeholder="Tuliskan instruksi atau informasi tambahan untuk siswa..."
                            class="w-full min-h-[120px] px-3.5 py-3 bg-[#FAFBFD] hover:bg-white focus:bg-white border border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15 rounded-[11px] text-sm text-[#263445] placeholder:text-[#94A3B8] leading-relaxed resize-y transition-all outline-none"
                        ></textarea>
                    </div>

                    <!-- 5. Pengaturan Tambahan sesuai Jenis Aktivitas -->

                    <!-- 5a. Video URL -->
                    <div x-show="activityType === 'video'" x-cloak>
                        <label for="add_activity_video_url" class="block text-xs font-semibold text-[#263445] mb-1.5">
                            URL Video YouTube <span class="font-normal text-[#718096]">(Opsional)</span>
                        </label>
                        <input
                            id="add_activity_video_url"
                            type="url"
                            name="video_url"
                            x-model="videoUrl"
                            :disabled="activityType !== 'video'"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="w-full min-h-[46px] px-3.5 py-2.5 bg-[#FAFBFD] hover:bg-white focus:bg-white border border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15 rounded-[11px] text-sm text-[#263445] placeholder:text-[#94A3B8] transition-all outline-none"
                        >
                    </div>

                    <!-- 5b. Upload Berkas Materi -->
                    <div x-show="activityType === 'file'" x-cloak>
                        <label class="block text-xs font-semibold text-[#263445] mb-1.5">
                            Berkas Dokumen Materi
                        </label>
                        <div class="border-2 border-dashed border-[#CBD5E1] hover:border-[#438FCE] rounded-xl p-4 text-center bg-[#FAFBFD] hover:bg-white transition-all cursor-pointer relative">
                            <input
                                x-ref="fileInput"
                                type="file"
                                name="document"
                                :disabled="activityType !== 'file'"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar"
                                @change="handleFileChange"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            >
                            <div x-show="!selectedFileName" class="space-y-1.5 py-1">
                                <div class="w-10 h-10 rounded-xl bg-[#DFEAF9]/70 text-[#438FCE] flex items-center justify-center mx-auto">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-[#263445]">Klik atau seret berkas ke area ini</p>
                                <p class="text-[11px] text-[#718096]">Mendukung PDF, DOC, DOCX, PPT, PPTX, TXT, atau ZIP (Maks. 15MB)</p>
                            </div>
                            <div x-show="selectedFileName" x-cloak class="flex items-center justify-between gap-3 bg-white px-3.5 py-2.5 rounded-xl border border-[#D7DFE9] relative z-10">
                                <div class="flex items-center gap-2.5 min-w-0 text-left">
                                    <span class="w-8 h-8 rounded-lg bg-[#E0F0E6] text-[#32865B] flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <span class="text-xs font-semibold text-[#263445] block truncate" x-text="selectedFileName"></span>
                                        <span class="text-[11px] text-[#718096] block" x-text="selectedFileSize"></span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click.stop="clearFile()"
                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold text-[#C54B4B] hover:bg-[#FDF0F0] transition-colors shrink-0"
                                    title="Hapus Berkas"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 5c. Pengaturan Kuis / Ujian Online -->
                    <div x-show="activityType === 'quiz'" x-cloak class="space-y-4">
                        <div class="p-4 bg-[#F8FAFD] rounded-xl border border-[#E5EAF0] space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <label class="block font-semibold text-[#263445] text-xs">Durasi Pengerjaan Ujian</label>
                                <span class="text-[11px] font-semibold text-[#2E6C9E] bg-[#DFEAF9] px-2.5 py-0.5 rounded-md" x-text="durationLabel"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-[#718096] mb-1">Jam</label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            name="duration_hours"
                                            min="0"
                                            max="24"
                                            x-model="hours"
                                            :disabled="activityType !== 'quiz'"
                                            class="w-full min-h-[44px] pl-3.5 pr-12 py-2 bg-white border border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15 rounded-[11px] text-sm font-semibold text-[#263445] outline-none"
                                            placeholder="0"
                                        >
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#718096] text-xs">Jam</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-[#718096] mb-1">Menit</label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            name="duration_minutes"
                                            min="0"
                                            max="59"
                                            step="5"
                                            x-model="minutes"
                                            :disabled="activityType !== 'quiz'"
                                            class="w-full min-h-[44px] pl-3.5 pr-14 py-2 bg-white border border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15 rounded-[11px] text-sm font-semibold text-[#263445] outline-none"
                                            placeholder="0"
                                        >
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#718096] text-xs">Menit</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span class="text-[11px] text-[#718096] mr-1">Cepat:</span>
                                <template x-for="preset in [[0,30,'30 Menit'],[0,45,'45 Menit'],[1,0,'1 Jam'],[1,30,'1.5 Jam'],[2,0,'2 Jam'],[0,0,'Tanpa Batas']]" :key="preset[2]">
                                    <button
                                        type="button"
                                        @click="setDurationPreset(preset[0], preset[1])"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-white border border-[#D7DFE9] text-[#263445] hover:border-[#438FCE] hover:text-[#438FCE] transition-colors"
                                        x-text="preset[2]"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <!-- Waktu Mulai & Batas Waktu Kuis (2 Kolom Desktop, 1 Kolom Mobile) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @include('kelas.partials.datetime-field', [
                                'fieldKey' => 'quizStart',
                                'inputName' => 'start_time',
                                'label' => 'Waktu Mulai Akses',
                                'required' => false,
                                'disabledExpr' => "activityType !== 'quiz'",
                                'defaultTime' => '08:00',
                                'popoverPlacement' => 'up',
                            ])
                            @include('kelas.partials.datetime-field', [
                                'fieldKey' => 'quizDeadline',
                                'inputName' => 'deadline',
                                'label' => 'Batas Waktu Akses (Deadline)',
                                'required' => false,
                                'disabledExpr' => "activityType !== 'quiz'",
                                'defaultTime' => '23:59',
                                'popoverPlacement' => 'up',
                            ])
                        </div>
                        <p x-show="activityType === 'quiz' && errors.quiz_range" x-cloak class="text-xs text-[#C54B4B] font-medium" x-text="errors.quiz_range"></p>

                        <!-- Anti-Cheat Checkbox -->
                        <div class="p-3.5 bg-[#FAFBFD] rounded-xl border border-[#E5EAF0]">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="detect_tab_switch"
                                    value="1"
                                    x-model="detectTabSwitch"
                                    :disabled="activityType !== 'quiz'"
                                    class="mt-0.5 rounded border-[#D7DFE9] text-[#438FCE] focus:ring-[#438FCE] h-4 w-4"
                                >
                                <div>
                                    <span class="block font-semibold text-[#263445] text-xs">Aktifkan Deteksi Pindah Tab (Anti-Cheating)</span>
                                    <span class="block text-[11px] text-[#718096] mt-0.5 leading-relaxed">
                                        Memantau dan mencatat waktu apabila siswa berpindah tab atau meninggalkan halaman ujian.
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 5d. Pengaturan Tugas / Assignment -->
                    <div x-show="activityType === 'assignment'" x-cloak class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @include('kelas.partials.datetime-field', [
                                'fieldKey' => 'assignStart',
                                'inputName' => 'start_time',
                                'label' => 'Waktu Mulai Penugasan',
                                'required' => false,
                                'disabledExpr' => "activityType !== 'assignment'",
                                'defaultTime' => '08:00',
                                'popoverPlacement' => 'up',
                            ])
                            @include('kelas.partials.datetime-field', [
                                'fieldKey' => 'assignDeadline',
                                'inputName' => 'deadline',
                                'label' => 'Batas Waktu (Deadline)',
                                'required' => false,
                                'disabledExpr' => "activityType !== 'assignment'",
                                'defaultTime' => '23:59',
                                'popoverPlacement' => 'up',
                            ])
                        </div>
                        <p x-show="activityType === 'assignment' && errors.assign_range" x-cloak class="text-xs text-[#C54B4B] font-medium" x-text="errors.assign_range"></p>
                    </div>

                    <!-- 5e. Pengaturan Tanggal & Waktu Presensi / Absensi -->
                    <div x-show="activityType === 'attendance'" x-cloak class="space-y-3">
                        <div class="p-4 bg-[#F8FAFD] rounded-xl border border-[#E5EAF0] space-y-3.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-[#263445]">Jadwal Sesi Presensi</span>
                                <span
                                    x-show="attendanceDurationSummary"
                                    class="text-[11px] font-medium text-[#2E6C9E] bg-[#DFEAF9] px-2.5 py-0.5 rounded-md"
                                    x-text="attendanceDurationSummary"
                                ></span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @include('kelas.partials.datetime-field', [
                                    'fieldKey' => 'attStart',
                                    'inputName' => 'start_time',
                                    'label' => 'Waktu Mulai',
                                    'required' => true,
                                    'disabledExpr' => "activityType !== 'attendance'",
                                    'defaultTime' => '08:00',
                                    'popoverPlacement' => 'up',
                                ])
                                @include('kelas.partials.datetime-field', [
                                    'fieldKey' => 'attEnd',
                                    'inputName' => 'end_time',
                                    'label' => 'Waktu Selesai',
                                    'required' => true,
                                    'disabledExpr' => "activityType !== 'attendance'",
                                    'defaultTime' => '10:00',
                                    'popoverPlacement' => 'up',
                                ])
                            </div>

                            <p x-show="errors.att_range" x-cloak class="text-xs text-[#C54B4B] font-medium flex items-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span x-text="errors.att_range"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- C. Modal Footer (Sticky, Selalu Terlihat, Rata Kanan) -->
                <div class="px-5 sm:px-6 py-3.5 border-t border-[#E5EAF0] bg-[#FAFBFD] flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 shrink-0">
                    <button
                        type="button"
                        x-on:click="$dispatch('close')"
                        class="w-full sm:w-auto min-h-[42px] px-4 py-2 bg-white border border-[#D7DFE9] rounded-[11px] text-xs sm:text-sm font-medium text-[#263445] hover:bg-[#F5F8FC] transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="w-full sm:w-auto min-h-[42px] px-5 py-2 bg-[#438FCE] hover:bg-[#387EB8] text-white rounded-[11px] text-xs sm:text-sm font-semibold shadow-2xs transition-colors"
                        x-text="submitButtonLabel"
                    ></button>
                </div>
            </form>
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

        <!-- Modal Konfirmasi Hapus Pertemuan -->
        <x-modal name="delete-meeting-modal" maxWidth="md" focusable>
            <form
                method="post"
                action="{{ route('kelas.meetings.destroy', $course->id) }}"
                x-data="{ topic: '', activityCount: 0 }"
                x-on:open-delete-meeting.window="topic = $event.detail.topic || ''; activityCount = $event.detail.activityCount || 0"
                class="bg-white overflow-hidden"
            >
                @csrf
                @method('DELETE')
                <input type="hidden" name="topic" :value="topic">

                <div class="px-5 py-4 border-b border-[#E5EAF0] flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-8 h-8 rounded-lg bg-[#FDF0F0] text-[#C54B4B] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </span>
                        <h2 class="text-base font-bold text-[#263445] truncate">Hapus Pertemuan</h2>
                    </div>
                    <button type="button" x-on:click="$dispatch('close')" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] transition-colors shrink-0" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-5 space-y-3">
                    <p class="text-sm text-[#263445] leading-relaxed">
                        Apakah Anda yakin ingin menghapus pertemuan &ldquo;<strong x-text="topic"></strong>&rdquo;?
                    </p>
                    <div class="p-3 bg-[#FDF0F0] border border-[#F8D5D5] rounded-xl text-xs text-[#C54B4B] leading-relaxed">
                        <strong>Peringatan:</strong> Seluruh <span class="font-bold" x-text="activityCount"></span> aktivitas di dalam pertemuan ini (materi, berkas, kuis, penugasan, pengumpulan, serta presensi) akan ikut terhapus secara permanen.
                    </div>
                </div>

                <div class="px-5 py-3.5 border-t border-[#E5EAF0] bg-[#FAFBFD] flex justify-end gap-2.5">
                    <button type="button" x-on:click="$dispatch('close')" class="min-h-[40px] px-4 py-2 bg-white border border-[#D7DFE9] rounded-[11px] text-xs sm:text-sm font-medium text-[#263445] hover:bg-[#F5F8FC] transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="min-h-[40px] px-4 py-2 bg-[#C54B4B] hover:bg-red-700 text-white rounded-[11px] text-xs sm:text-sm font-semibold shadow-2xs transition-colors">
                        Ya, Hapus Pertemuan
                    </button>
                </div>
            </form>
        </x-modal>
        @endif

        @if(Auth::user()->role === 'siswa')
        <!-- Modal Konfirmasi Keluar dari Kelas -->
        <x-modal name="leave-course-modal" focusable>
            <form method="post" action="{{ route('kelas.leave', $course->id) }}" class="p-6">
                @csrf
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4 border border-rose-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </div>

                <h3 class="text-base font-bold text-gray-900 mb-1.5">Konfirmasi Keluar dari Kelas</h3>
                <p class="text-xs text-gray-600 leading-relaxed mb-4">
                    Apakah Anda yakin ingin mengundurkan diri dan keluar dari kelas <strong>"{{ $course->title }}"</strong>?
                </p>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 leading-relaxed mb-6">
                    <strong>Catatan Penting:</strong> Kelas ini tidak akan lagi tampil di dashboard Anda dan Anda tidak dapat lagi mengakses materi atau mengerjakan kuis di kelas ini. Seluruh riwayat pengumpulan tugas dan kuis yang sudah pernah Anda selesaikan tetap tersimpan di buku nilai guru.
                </div>

                <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100 text-xs">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-semibold shadow-2xs transition-colors cursor-pointer">
                        Ya, Keluar dari Kelas
                    </button>
                </div>
            </form>
        </x-modal>
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('addActivityModal', (config) => {
                const isoToDisplay = (iso) => {
                    if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '';
                    const [y, m, d] = iso.split('-');
                    return `${d}/${m}/${y}`;
                };

                const parseDisplayToIso = (display) => {
                    const clean = String(display || '').trim();
                    const m = clean.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
                    if (!m) return null;
                    const day = parseInt(m[1], 10);
                    const month = parseInt(m[2], 10);
                    const year = parseInt(m[3], 10);
                    if (month < 1 || month > 12 || day < 1 || day > 31) return null;
                    const probe = new Date(year, month - 1, day);
                    if (probe.getFullYear() !== year || probe.getMonth() !== month - 1 || probe.getDate() !== day) {
                        return null;
                    }
                    return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                };

                const pad2 = (n) => String(n).padStart(2, '0');
                const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

                const slots = [];
                for (let h = 6; h < 24; h++) {
                    slots.push(`${pad2(h)}:00`);
                    slots.push(`${pad2(h)}:30`);
                }
                slots.push('23:59');

                const makeDateField = (iso = '', time = '') => ({
                    date: iso,
                    dateDisplay: isoToDisplay(iso),
                    time: time,
                });

                return {
                    courseId: config.courseId,
                    courseTitle: config.courseTitle || '',
                    meetings: Array.isArray(config.meetings) ? config.meetings : [],
                    routes: config.routes,

                    activityType: 'file',
                    typeDropdownOpen: false,
                    meetingDropdownOpen: false,
                    meetingSearch: '',
                    selectedTopic: '',
                    lockMeeting: false,
                    title: '',
                    notes: '',
                    videoUrl: '',
                    selectedFileName: '',
                    selectedFileSize: '',
                    hours: 1,
                    minutes: 30,
                    detectTabSwitch: false,

                    attStart: makeDateField(config.initialStartDate, config.initialStartTime),
                    attEnd: makeDateField(config.initialEndDate, config.initialEndTime),
                    quizStart: makeDateField('', ''),
                    quizDeadline: makeDateField('', ''),
                    assignStart: makeDateField('', ''),
                    assignDeadline: makeDateField('', ''),

                    activePicker: null,
                    calYear: new Date().getFullYear(),
                    calMonth: new Date().getMonth(),
                    timeSlots: slots,
                    errors: {},

                    types: [
                        {
                            id: 'label',
                            name: 'Label / Pengantar',
                            desc: 'Teks pengantar atau salam pembuka/penutup sesi',
                            badgeClass: 'bg-[#FCEAD9] text-[#B8873E]',
                            iconPath: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
                        },
                        {
                            id: 'file',
                            name: 'Berkas Materi',
                            desc: 'Upload dokumen modul PDF, Word, atau PPTX',
                            badgeClass: 'bg-[#E0F0E6] text-[#32865B]',
                            iconPath: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        },
                        {
                            id: 'page',
                            name: 'Halaman Materi',
                            desc: 'Materi teks artikel untuk dibaca langsung',
                            badgeClass: 'bg-[#DDF1F0] text-[#419D98]',
                            iconPath: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                        },
                        {
                            id: 'video',
                            name: 'Video Pembelajaran',
                            desc: 'Sematkan video pembelajaran dari YouTube',
                            badgeClass: 'bg-[#FDF0F0] text-[#C54B4B]',
                            iconPath: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z',
                        },
                        {
                            id: 'quiz',
                            name: 'Kuis / Ujian Online',
                            desc: 'Ujian pilihan ganda & esai dengan anti-cheat',
                            badgeClass: 'bg-[#DFEAF9] text-[#438FCE]',
                            iconPath: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                        },
                        {
                            id: 'assignment',
                            name: 'Tugas / Assignment',
                            desc: 'Penugasan mandiri pengumpulan berkas',
                            badgeClass: 'bg-[#EEE5F8] text-[#895EB5]',
                            iconPath: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
                        },
                        {
                            id: 'attendance',
                            name: 'Presensi / Absensi',
                            desc: 'Pencatatan daftar hadir siswa per sesi',
                            badgeClass: 'bg-[#E0F0E6] text-[#32865B]',
                            iconPath: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                        },
                    ],

                    get currentType() {
                        return this.types.find((t) => t.id === this.activityType) || this.types[1];
                    },

                    get isMaterialType() {
                        return ['file', 'page', 'video', 'label'].includes(this.activityType);
                    },

                    get formAction() {
                        if (this.activityType === 'quiz' || this.activityType === 'assignment') {
                            return this.routes.quizzes;
                        }
                        if (this.activityType === 'attendance') {
                            return this.routes.attendances;
                        }
                        return this.routes.materials;
                    },

                    get titleFieldLabel() {
                        if (this.activityType === 'attendance') return 'Judul Sesi Presensi';
                        if (this.activityType === 'quiz') return 'Judul Kuis / Ujian';
                        if (this.activityType === 'assignment') return 'Judul Tugas';
                        return 'Judul Aktivitas';
                    },

                    get titlePlaceholder() {
                        if (this.activityType === 'attendance') return 'Contoh: Presensi Kehadiran Pertemuan 01';
                        if (this.activityType === 'quiz') return 'Contoh: Kuis 1: Konsep Dasar';
                        if (this.activityType === 'assignment') return 'Contoh: Tugas Praktikum Mandiri 1';
                        return 'Contoh: Modul Pengantar Pertemuan 1';
                    },

                    get notesFieldLabel() {
                        if (this.activityType === 'label') return 'Isi Teks / Pengantar';
                        if (this.activityType === 'page') return 'Isi Materi Lengkap';
                        return 'Panduan / Keterangan';
                    },

                    get submitButtonLabel() {
                        if (this.activityType === 'attendance') return 'Buat Sesi Presensi';
                        if (this.activityType === 'quiz') return 'Buat Kuis & Lanjut Soal';
                        if (this.activityType === 'assignment') return 'Simpan Tugas';
                        return 'Simpan Aktivitas';
                    },

                    get totalMinutes() {
                        return (parseInt(this.hours, 10) || 0) * 60 + (parseInt(this.minutes, 10) || 0);
                    },

                    get durationLabel() {
                        const h = parseInt(this.hours, 10) || 0;
                        const m = parseInt(this.minutes, 10) || 0;
                        if (h === 0 && m === 0) return 'Tanpa Batas Durasi';
                        const parts = [];
                        if (h > 0) parts.push(`${h} Jam`);
                        if (m > 0) parts.push(`${m} Menit`);
                        return `${parts.join(' ')} (${this.totalMinutes} Menit Total)`;
                    },

                    get filteredMeetings() {
                        const q = this.meetingSearch.trim().toLowerCase();
                        if (!q) return this.meetings;
                        return this.meetings.filter((m) => {
                            const hay = `${m.display_label} ${m.topic} ${m.meeting_number} ${m.course_title}`.toLowerCase();
                            return hay.includes(q);
                        });
                    },

                    get canCreateNewTopicFromSearch() {
                        const q = this.meetingSearch.trim();
                        if (!q) return false;
                        const lower = q.toLowerCase();
                        return !this.meetings.some((m) =>
                            m.topic.toLowerCase() === lower || m.display_label.toLowerCase() === lower
                        );
                    },

                    get selectedMeetingObj() {
                        if (!this.selectedTopic) return null;
                        const found = this.meetings.find((m) => m.topic === this.selectedTopic);
                        if (found) return found;
                        return {
                            topic: this.selectedTopic,
                            meeting_number: '+',
                            display_label: this.selectedTopic,
                            course_title: this.courseTitle,
                            activity_count: 0,
                            isNew: true,
                        };
                    },

                    get selectedMeetingSubtitle() {
                        const obj = this.selectedMeetingObj;
                        if (!obj) return '';
                        if (obj.isNew) return `${this.courseTitle} • Pertemuan baru`;
                        return `${obj.course_title} • ${obj.activity_count} aktivitas`;
                    },

                    get calendarMonthLabel() {
                        return `${monthNames[this.calMonth]} ${this.calYear}`;
                    },

                    get calendarDays() {
                        const firstOfMonth = new Date(this.calYear, this.calMonth, 1);
                        const startWeekday = (firstOfMonth.getDay() + 6) % 7; // Senin = 0
                        const gridStart = new Date(this.calYear, this.calMonth, 1 - startWeekday);
                        const today = new Date();
                        const todayIso = `${today.getFullYear()}-${pad2(today.getMonth() + 1)}-${pad2(today.getDate())}`;

                        const days = [];
                        for (let i = 0; i < 42; i++) {
                            const d = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i);
                            const iso = `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
                            days.push({
                                day: d.getDate(),
                                iso,
                                inCurrentMonth: d.getMonth() === this.calMonth,
                                isToday: iso === todayIso,
                            });
                        }
                        return days;
                    },

                    get attendanceDurationSummary() {
                        const s = this.combineDateTime(this.attStart);
                        const e = this.combineDateTime(this.attEnd);
                        if (!s || !e) return '';
                        const startMs = new Date(s).getTime();
                        const endMs = new Date(e).getTime();
                        if (Number.isNaN(startMs) || Number.isNaN(endMs) || endMs <= startMs) return '';
                        const diffMin = Math.round((endMs - startMs) / 60000);
                        const h = Math.floor(diffMin / 60);
                        const m = diffMin % 60;
                        if (h > 0 && m > 0) return `Durasi: ${h} jam ${m} menit`;
                        if (h > 0) return `Durasi: ${h} jam`;
                        return `Durasi: ${m} menit`;
                    },

                    openWithContext(detail = {}) {
                        const incomingTopic = typeof detail.meetingTopic === 'string' ? detail.meetingTopic : '';
                        this.lockMeeting = Boolean(detail.lockMeeting && incomingTopic);
                        this.selectedTopic = incomingTopic;
                        this.meetingSearch = '';
                        this.typeDropdownOpen = false;
                        this.meetingDropdownOpen = false;
                        this.activePicker = null;
                        this.errors = {};
                        this.$nextTick(() => {
                            if (this.$refs.notesTextarea) {
                                this.autoResizeTextarea(this.$refs.notesTextarea);
                            }
                        });
                    },

                    selectType(id) {
                        this.activityType = id;
                        this.typeDropdownOpen = false;
                        this.activePicker = null;
                        this.errors = {};
                    },

                    toggleMeetingDropdown() {
                        this.typeDropdownOpen = false;
                        this.meetingDropdownOpen = !this.meetingDropdownOpen;
                        if (this.meetingDropdownOpen) {
                            this.$nextTick(() => {
                                if (this.$refs.meetingSearchInput) {
                                    this.$refs.meetingSearchInput.focus();
                                }
                            });
                        }
                    },

                    selectMeeting(topic) {
                        this.selectedTopic = topic;
                        this.meetingDropdownOpen = false;
                        this.meetingSearch = '';
                        delete this.errors.topic;
                    },

                    selectFirstOrNewMeeting() {
                        if (this.filteredMeetings.length > 0) {
                            this.selectMeeting(this.filteredMeetings[0].topic);
                        } else if (this.canCreateNewTopicFromSearch) {
                            this.selectMeeting(this.meetingSearch.trim());
                        }
                    },

                    setDurationPreset(h, m) {
                        this.hours = h;
                        this.minutes = m;
                    },

                    autoResizeTextarea(el) {
                        if (!el) return;
                        el.style.height = 'auto';
                        el.style.height = Math.max(120, Math.min(el.scrollHeight, 320)) + 'px';
                    },

                    handleFileChange(event) {
                        const file = event.target.files[0];
                        if (!file) {
                            this.selectedFileName = '';
                            this.selectedFileSize = '';
                            return;
                        }
                        this.selectedFileName = file.name;
                        const kb = file.size / 1024;
                        this.selectedFileSize = kb >= 1024 ? `${(kb / 1024).toFixed(2)} MB` : `${Math.max(1, Math.round(kb))} KB`;
                    },

                    clearFile() {
                        this.selectedFileName = '';
                        this.selectedFileSize = '';
                        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                    },

                    combineDateTime(fieldObj) {
                        if (!fieldObj || !fieldObj.date) return '';
                        const time = /^\d{2}:\d{2}$/.test(fieldObj.time || '') ? fieldObj.time : '00:00';
                        return `${fieldObj.date}T${time}`;
                    },

                    openDatePicker(pickerId, fieldObj) {
                        const base = fieldObj && fieldObj.date && /^\d{4}-\d{2}-\d{2}$/.test(fieldObj.date)
                            ? new Date(fieldObj.date + 'T00:00:00')
                            : new Date();
                        this.calYear = base.getFullYear();
                        this.calMonth = base.getMonth();
                        this.activePicker = pickerId;
                    },

                    toggleDatePicker(pickerId, fieldObj) {
                        if (this.activePicker === pickerId) {
                            this.activePicker = null;
                        } else {
                            this.openDatePicker(pickerId, fieldObj);
                        }
                    },

                    togglePicker(pickerId) {
                        this.activePicker = this.activePicker === pickerId ? null : pickerId;
                    },

                    closePicker(pickerId) {
                        if (this.activePicker === pickerId) {
                            this.activePicker = null;
                        }
                    },

                    prevMonth() {
                        if (this.calMonth === 0) {
                            this.calMonth = 11;
                            this.calYear -= 1;
                        } else {
                            this.calMonth -= 1;
                        }
                    },

                    nextMonth() {
                        if (this.calMonth === 11) {
                            this.calMonth = 0;
                            this.calYear += 1;
                        } else {
                            this.calMonth += 1;
                        }
                    },

                    selectCalendarDay(fieldObj, isoDate, keyPrefix, defaultTime) {
                        fieldObj.date = isoDate;
                        fieldObj.dateDisplay = isoToDisplay(isoDate);
                        if (!fieldObj.time) {
                            fieldObj.time = defaultTime || '08:00';
                        }
                        delete this.errors[`${keyPrefix}_date`];
                        this.validateRanges();
                        this.activePicker = null;
                    },

                    selectToday(fieldObj, keyPrefix, defaultTime) {
                        const now = new Date();
                        const iso = `${now.getFullYear()}-${pad2(now.getMonth() + 1)}-${pad2(now.getDate())}`;
                        this.selectCalendarDay(fieldObj, iso, keyPrefix, defaultTime);
                    },

                    clearDateTimeField(fieldObj, keyPrefix) {
                        fieldObj.date = '';
                        fieldObj.dateDisplay = '';
                        fieldObj.time = '';
                        delete this.errors[`${keyPrefix}_date`];
                        delete this.errors[`${keyPrefix}_time`];
                        this.validateRanges();
                        this.activePicker = null;
                    },

                    onManualDateInput(fieldObj, rawValue, keyPrefix) {
                        const clean = String(rawValue || '').trim();
                        if (!clean) {
                            fieldObj.date = '';
                            delete this.errors[`${keyPrefix}_date`];
                            this.validateRanges();
                            return;
                        }
                        const iso = parseDisplayToIso(clean);
                        if (iso) {
                            fieldObj.date = iso;
                            delete this.errors[`${keyPrefix}_date`];
                            this.validateRanges();
                        }
                    },

                    normalizeDateField(fieldObj, keyPrefix) {
                        const clean = String(fieldObj.dateDisplay || '').trim();
                        if (!clean) {
                            fieldObj.date = '';
                            delete this.errors[`${keyPrefix}_date`];
                            return;
                        }
                        const iso = parseDisplayToIso(clean);
                        if (iso) {
                            fieldObj.date = iso;
                            fieldObj.dateDisplay = isoToDisplay(iso);
                            delete this.errors[`${keyPrefix}_date`];
                            this.validateRanges();
                        } else {
                            fieldObj.date = '';
                            this.errors[`${keyPrefix}_date`] = 'Gunakan format DD/MM/YYYY';
                        }
                    },

                    selectTimeSlot(fieldObj, slot, keyPrefix) {
                        fieldObj.time = slot;
                        delete this.errors[`${keyPrefix}_time`];
                        this.validateRanges();
                        this.activePicker = null;
                    },

                    onManualTimeInput(fieldObj, rawValue, keyPrefix) {
                        const clean = String(rawValue || '').trim();
                        if (!clean || /^([01]\d|2[0-3]):[0-5]\d$/.test(clean)) {
                            delete this.errors[`${keyPrefix}_time`];
                            this.validateRanges();
                        }
                    },

                    normalizeTimeField(fieldObj, keyPrefix) {
                        const clean = String(fieldObj.time || '').trim();
                        if (!clean) {
                            delete this.errors[`${keyPrefix}_time`];
                            return;
                        }
                        const m = clean.match(/^(\d{1,2})[:\.]?(\d{2})$/);
                        if (m) {
                            const h = parseInt(m[1], 10);
                            const min = parseInt(m[2], 10);
                            if (h >= 0 && h <= 23 && min >= 0 && min <= 59) {
                                fieldObj.time = `${pad2(h)}:${pad2(min)}`;
                                delete this.errors[`${keyPrefix}_time`];
                                this.validateRanges();
                                return;
                            }
                        }
                        this.errors[`${keyPrefix}_time`] = 'Format jam HH:MM';
                    },

                    validateRanges() {
                        delete this.errors.att_range;
                        delete this.errors.quiz_range;
                        delete this.errors.assign_range;

                        if (this.activityType === 'attendance' && this.attStart.date && this.attEnd.date) {
                            const s = new Date(this.combineDateTime(this.attStart)).getTime();
                            const e = new Date(this.combineDateTime(this.attEnd)).getTime();
                            if (!Number.isNaN(s) && !Number.isNaN(e) && e <= s) {
                                this.errors.att_range = 'Waktu selesai presensi tidak boleh lebih awal atau sama dengan waktu mulai.';
                            }
                        }

                        if (this.activityType === 'quiz' && this.quizStart.date && this.quizDeadline.date) {
                            const s = new Date(this.combineDateTime(this.quizStart)).getTime();
                            const e = new Date(this.combineDateTime(this.quizDeadline)).getTime();
                            if (!Number.isNaN(s) && !Number.isNaN(e) && e < s) {
                                this.errors.quiz_range = 'Batas waktu ujian tidak boleh lebih awal dari waktu mulai.';
                            }
                        }

                        if (this.activityType === 'assignment' && this.assignStart.date && this.assignDeadline.date) {
                            const s = new Date(this.combineDateTime(this.assignStart)).getTime();
                            const e = new Date(this.combineDateTime(this.assignDeadline)).getTime();
                            if (!Number.isNaN(s) && !Number.isNaN(e) && e < s) {
                                this.errors.assign_range = 'Batas waktu tugas tidak boleh lebih awal dari waktu mulai.';
                            }
                        }
                    },

                    handleSubmit(event) {
                        const nextErrors = {};

                        if (!this.selectedTopic || !this.selectedTopic.trim()) {
                            nextErrors.topic = 'Silakan pilih atau buat pertemuan terlebih dahulu.';
                        }

                        if (!this.title || !this.title.trim()) {
                            nextErrors.title = 'Judul aktivitas tidak boleh kosong.';
                        }

                        if (this.activityType === 'attendance') {
                            this.normalizeDateField(this.attStart, 'attStart');
                            this.normalizeTimeField(this.attStart, 'attStart');
                            this.normalizeDateField(this.attEnd, 'attEnd');
                            this.normalizeTimeField(this.attEnd, 'attEnd');

                            if (!this.attStart.date) nextErrors.attStart_date = this.errors.attStart_date || 'Tanggal mulai wajib diisi.';
                            if (!this.attStart.time) nextErrors.attStart_time = this.errors.attStart_time || 'Jam mulai wajib diisi.';
                            if (!this.attEnd.date) nextErrors.attEnd_date = this.errors.attEnd_date || 'Tanggal selesai wajib diisi.';
                            if (!this.attEnd.time) nextErrors.attEnd_time = this.errors.attEnd_time || 'Jam selesai wajib diisi.';

                            if (this.attStart.date && this.attStart.time && this.attEnd.date && this.attEnd.time) {
                                const s = new Date(this.combineDateTime(this.attStart)).getTime();
                                const e = new Date(this.combineDateTime(this.attEnd)).getTime();
                                if (e <= s) {
                                    nextErrors.att_range = 'Waktu selesai presensi tidak boleh lebih awal atau sama dengan waktu mulai.';
                                }
                            }
                        }

                        if (this.activityType === 'quiz') {
                            this.normalizeDateField(this.quizStart, 'quizStart');
                            this.normalizeTimeField(this.quizStart, 'quizStart');
                            this.normalizeDateField(this.quizDeadline, 'quizDeadline');
                            this.normalizeTimeField(this.quizDeadline, 'quizDeadline');
                            if (this.errors.quizStart_date) nextErrors.quizStart_date = this.errors.quizStart_date;
                            if (this.errors.quizStart_time) nextErrors.quizStart_time = this.errors.quizStart_time;
                            if (this.errors.quizDeadline_date) nextErrors.quizDeadline_date = this.errors.quizDeadline_date;
                            if (this.errors.quizDeadline_time) nextErrors.quizDeadline_time = this.errors.quizDeadline_time;
                            if (this.quizStart.date && this.quizDeadline.date) {
                                const s = new Date(this.combineDateTime(this.quizStart)).getTime();
                                const e = new Date(this.combineDateTime(this.quizDeadline)).getTime();
                                if (e < s) nextErrors.quiz_range = 'Batas waktu ujian tidak boleh lebih awal dari waktu mulai.';
                            }
                        }

                        if (this.activityType === 'assignment') {
                            this.normalizeDateField(this.assignStart, 'assignStart');
                            this.normalizeTimeField(this.assignStart, 'assignStart');
                            this.normalizeDateField(this.assignDeadline, 'assignDeadline');
                            this.normalizeTimeField(this.assignDeadline, 'assignDeadline');
                            if (this.errors.assignStart_date) nextErrors.assignStart_date = this.errors.assignStart_date;
                            if (this.errors.assignStart_time) nextErrors.assignStart_time = this.errors.assignStart_time;
                            if (this.errors.assignDeadline_date) nextErrors.assignDeadline_date = this.errors.assignDeadline_date;
                            if (this.errors.assignDeadline_time) nextErrors.assignDeadline_time = this.errors.assignDeadline_time;
                            if (this.assignStart.date && this.assignDeadline.date) {
                                const s = new Date(this.combineDateTime(this.assignStart)).getTime();
                                const e = new Date(this.combineDateTime(this.assignDeadline)).getTime();
                                if (e < s) nextErrors.assign_range = 'Batas waktu tugas tidak boleh lebih awal dari waktu mulai.';
                            }
                        }

                        this.errors = nextErrors;
                        if (Object.keys(nextErrors).length > 0) {
                            event.preventDefault();
                        }
                    },
                };
            });

            Alpine.data('topicAccordionManager', (config) => ({
                userId: config.userId,
                courseId: config.courseId,
                topicIds: Array.isArray(config.topicIds) ? config.topicIds : [],
                states: {},

                get storageKey() {
                    return `eduva_alur_accordion_u${this.userId}_c${this.courseId}`;
                },

                get allOpen() {
                    if (this.topicIds.length === 0) {
                        return false;
                    }
                    return this.topicIds.every((id) => Boolean(this.states[id]));
                },

                init() {
                    const saved = this.readSavedStates();
                    const reconciled = {};

                    // Gunakan ID pertemuan (topik) yang stabil:
                    // - Pertemuan baru otomatis tertutup (false) tanpa mengubah status lainnya
                    // - Pertemuan yang dihapus tidak ikut disimpan lagi
                    for (const id of this.topicIds) {
                        reconciled[id] = Object.prototype.hasOwnProperty.call(saved, id)
                            ? Boolean(saved[id])
                            : false;
                    }

                    this.states = reconciled;
                    this.persistStates();
                },

                isOpen(topicId) {
                    return Boolean(this.states[topicId]);
                },

                toggleTopic(topicId) {
                    if (!Object.prototype.hasOwnProperty.call(this.states, topicId)) {
                        return;
                    }
                    this.states = {
                        ...this.states,
                        [topicId]: !this.states[topicId],
                    };
                    this.persistStates();
                },

                toggleAll() {
                    if (this.topicIds.length === 0) {
                        return;
                    }
                    const targetOpen = !this.allOpen;
                    const nextStates = {};
                    for (const id of this.topicIds) {
                        nextStates[id] = targetOpen;
                    }
                    this.states = nextStates;
                    this.persistStates();
                },

                readSavedStates() {
                    try {
                        const raw = window.localStorage.getItem(this.storageKey);
                        if (!raw) {
                            return {};
                        }
                        const parsed = JSON.parse(raw);
                        return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
                            ? parsed
                            : {};
                    } catch (e) {
                        return {};
                    }
                },

                persistStates() {
                    try {
                        window.localStorage.setItem(this.storageKey, JSON.stringify(this.states));
                    } catch (e) {
                        // Abaikan apabila penyimpanan lokal browser dibatasi
                    }
                },
            }));
        });
    </script>
</x-app-layout>

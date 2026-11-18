<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#438FCE] text-white flex items-center justify-center shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div>
                    <x-breadcrumb :items="['AI Tutor' => '']" />
                    <h2 class="font-bold text-xl text-[#263445] leading-tight">AI Tutor</h2>
                    <p class="text-xs text-[#718096]">Asisten belajar interaktif berbasis konteks kelas dan pertemuan</p>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-[#E0F0E6] text-[#32865B] border border-[#C4E4CF]">
                    <span class="w-2 h-2 rounded-full bg-[#32865B]"></span>
                    Gemini AI Siap
                </span>
            </div>
        </div>
    </x-slot>

    <!-- Main Container -->
    <div 
        x-data="aiTutorApp({
            activeConvId: {{ $activeConversation ? $activeConversation->id : 'null' }},
            activeCourseId: {{ $initialCourseId ? (int) $initialCourseId : 'null' }},
            activeMeetingId: {{ $initialMeetingId ? (int) $initialMeetingId : 'null' }},
            autoContext: {{ $isAutoContext ? 'true' : 'false' }},
            initialContextError: @js($contextError ?? ''),
            studyMode: '{{ $activeConversation ? $activeConversation->study_mode : 'tanya_jawab' }}',
            difficulty: '{{ $activeConversation ? $activeConversation->difficulty : 'menengah' }}',
            chatEndpoint: '{{ route('ai-tutor.chat') }}',
            meetingsEndpointTemplate: '{{ url('/ai-tutor/courses/__COURSE_ID__/meetings') }}',
            csrfToken: '{{ csrf_token() }}',
            courses: @js($courseOptions),
            initialMeetings: @js($initialMeetings->values()),
            initialMessages: @js($messages->map(function($m) {
                return [
                    'id' => $m->id,
                    'role' => $m->role,
                    'content' => $m->content,
                    'sources' => $m->sources,
                    'created_at' => $m->created_at->format('H:i')
                ];
            }))
        })" 
        class="-mx-2 sm:mx-0 h-[calc(100dvh-84px)] lg:h-[calc(100dvh-140px)] min-h-[420px] flex bg-white rounded-xl sm:rounded-2xl shadow-xs border border-[#E5EAF0] overflow-hidden relative z-0"
    >
        <!-- Sidebar: Conversation History -->
        <aside 
            x-cloak
            :class="historyOpen ? 'translate-x-0 pointer-events-auto' : '-translate-x-full pointer-events-none lg:translate-x-0 lg:pointer-events-auto'"
            class="absolute lg:static inset-y-0 left-0 z-30 w-64 lg:w-60 max-w-[85vw] bg-[#F8FAFD] border-r border-[#E5EAF0] flex flex-col transition-transform duration-200 ease-in-out shrink-0"
        >
            <!-- Sidebar Header -->
            <div class="p-3 border-b border-[#E5EAF0] flex items-center justify-between bg-white">
                <a 
                    href="{{ route('ai-tutor.new') }}" 
                    class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-[#438FCE] text-white text-xs font-semibold hover:bg-[#387EB8] transition-all shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Percakapan Baru
                </a>
                <button 
                    @click="historyOpen = false" 
                    class="lg:hidden ml-2 p-2 text-[#718096] hover:bg-[#F5F8FC] rounded-lg"
                    title="Tutup Menu"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Conversations List -->
            <div class="flex-1 overflow-y-auto p-2.5 space-y-1">
                <div class="px-2 py-1 text-[11px] font-bold text-[#718096] uppercase tracking-wider">
                    Riwayat Percakapan
                </div>

                @forelse($conversations as $conv)
                    <div 
                        class="group relative flex items-center justify-between rounded-xl px-3 py-2 text-xs transition-all {{ $activeConversation && $activeConversation->id === $conv->id ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#263445] hover:bg-white' }}"
                    >
                        <a 
                            href="{{ route('ai-tutor.index', ['conversation_id' => $conv->id]) }}" 
                            class="flex-1 min-w-0 pr-2"
                        >
                            <p class="truncate font-medium {{ $activeConversation && $activeConversation->id === $conv->id ? 'text-[#2E6C9E]' : 'text-[#263445]' }}">
                                {{ $conv->title }}
                            </p>
                            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-[#718096]">
                                <span class="shrink-0">{{ $conv->created_at->diffForHumans(null, true) }}</span>
                                @if($conv->course)
                                    <span>•</span>
                                    <span class="truncate max-w-[85px]">{{ $conv->course->title }}</span>
                                @endif
                                @if($conv->material)
                                    <span>•</span>
                                    <span class="truncate max-w-[85px]">{{ $conv->material->title }}</span>
                                @endif
                            </div>
                        </a>

                        <!-- Actions (Rename & Delete) -->
                        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button 
                                @click.prevent="openRenameModal({{ $conv->id }}, '{{ addslashes($conv->title) }}')" 
                                class="p-1 text-[#718096] hover:text-[#263445] rounded" 
                                title="Ganti Judul"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <form 
                                method="POST" 
                                action="{{ route('ai-tutor.delete', $conv->id) }}" 
                                onsubmit="return confirm('Hapus percakapan ini?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    class="p-1 text-[#718096] hover:text-red-600 rounded" 
                                    title="Hapus"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-8 text-center text-xs text-[#718096]">
                        Belum ada riwayat. Pilih kelas dan pertemuan lalu ajukan pertanyaan pertamamu!
                    </div>
                @endforelse
            </div>
        </aside>

        <!-- Backdrop for mobile/tablet history sidebar -->
        <div 
            x-show="historyOpen" 
            x-cloak
            @click="historyOpen = false" 
            x-transition.opacity 
            class="absolute inset-0 bg-black/30 z-20 lg:hidden"
        ></div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-white">
            
            <!-- Top Bar: Dynamic Course & Meeting Context + Study Mode -->
            <div class="px-2.5 sm:px-4 py-2 lg:py-2.5 border-b border-[#E5EAF0] bg-white flex flex-wrap items-center justify-between gap-2 lg:gap-2.5">
                <!-- Left: History Sidebar Toggle (< lg) + Context Selectors -->
                <div class="flex items-center gap-2 flex-wrap flex-1 min-w-0">
                    <button 
                        @click="historyOpen = !historyOpen; courseDropdownOpen = false; meetingDropdownOpen = false" 
                        class="lg:hidden p-1.5 text-[#718096] hover:bg-[#F5F8FC] rounded-lg shrink-0"
                        title="Daftar Percakapan"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </button>

                    <!-- Kondisi A: Konteks Otomatis dari Halaman Pertemuan -->
                    <template x-if="autoContext && !isEditingContext && selectedCourse">
                        <div class="flex items-center gap-2 bg-[#DFEAF9]/60 border border-[#C3DAF5] rounded-xl px-3 py-1.5 text-xs max-w-full min-w-0">
                            <svg class="w-4 h-4 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <div class="flex items-center gap-1.5 min-w-0 truncate">
                                <span class="font-bold text-[#2E6C9E] truncate" x-text="selectedCourse.title"></span>
                                <template x-if="selectedMeeting">
                                    <span class="flex items-center gap-1.5 min-w-0 truncate">
                                        <span class="text-[#438FCE]">/</span>
                                        <span class="font-medium text-[#263445] truncate" x-text="selectedMeeting.display_label"></span>
                                    </span>
                                </template>
                            </div>
                            <button 
                                type="button" 
                                @click="isEditingContext = true" 
                                class="ml-1 px-2 py-0.5 rounded-lg bg-white/80 hover:bg-white text-[11px] font-semibold text-[#438FCE] border border-[#C3DAF5] transition-colors shrink-0"
                            >
                                Ubah
                            </button>
                        </div>
                    </template>

                    <!-- Kondisi B: Searchable Course Dropdown + Dynamic Meeting Dropdown -->
                    <template x-if="!autoContext || isEditingContext || !selectedCourse">
                        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
                            
                            <!-- 1. Searchable Dropdown Kelas -->
                            <div class="relative w-full sm:w-56" @click.outside="courseDropdownOpen = false">
                                <button 
                                    type="button"
                                    @click="toggleCourseDropdown()"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-1.5 bg-[#F8FAFD] hover:bg-white border border-[#E5EAF0] hover:border-[#438FCE]/60 rounded-xl text-xs transition-all text-left"
                                    :class="courseDropdownOpen ? 'border-[#438FCE] ring-2 ring-[#438FCE]/15 bg-white' : ''"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-3.5 h-3.5 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        <span 
                                            class="truncate font-semibold" 
                                            :class="selectedCourse ? 'text-[#263445]' : 'text-[#718096]'"
                                            x-text="selectedCourse ? selectedCourse.title : (courses.length === 0 ? 'Belum Ada Kelas' : 'Pilih Kelas...')"
                                        ></span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-[#718096] shrink-0 transition-transform duration-150" :class="courseDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Popover Pencarian Kelas -->
                                <div 
                                    x-show="courseDropdownOpen" 
                                    x-transition.opacity.duration.150ms
                                    class="absolute left-0 mt-1.5 w-full sm:w-72 bg-white rounded-xl shadow-lg border border-[#E5EAF0] z-20 overflow-hidden"
                                    x-cloak
                                >
                                    <div class="p-2 border-b border-[#E5EAF0] bg-[#F8FAFD]">
                                        <div class="relative">
                                            <svg class="w-3.5 h-3.5 text-[#718096] absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                            <input 
                                                x-ref="courseSearchInput"
                                                x-model="courseSearch" 
                                                type="text" 
                                                placeholder="Cari nama kelas..." 
                                                class="w-full pl-8 pr-7 py-1.5 text-xs bg-white border border-[#E5EAF0] rounded-lg focus:border-[#438FCE] focus:ring-1 focus:ring-[#438FCE] text-[#263445] placeholder:text-[#718096]"
                                            />
                                            <button 
                                                x-show="courseSearch" 
                                                type="button" 
                                                @click="courseSearch = ''" 
                                                class="absolute right-2 top-1/2 -translate-y-1/2 text-[#718096] hover:text-[#263445] text-xs font-bold"
                                            >&times;</button>
                                        </div>
                                    </div>

                                    <div class="max-h-56 overflow-y-auto p-1.5 space-y-0.5">
                                        <template x-if="courses.length === 0">
                                            <div class="p-3 text-center text-xs text-[#718096] space-y-2">
                                                <p>Anda belum mengikuti kelas apa pun.</p>
                                                <a href="{{ route('kelas.index') }}" class="inline-block px-3 py-1 bg-[#438FCE] text-white rounded-lg font-medium hover:bg-[#387EB8]">
                                                    Gabung Kelas
                                                </a>
                                            </div>
                                        </template>

                                        <template x-if="courses.length > 0 && filteredCourses.length === 0">
                                            <div class="p-3 text-center text-xs text-[#718096]">
                                                Kelas "<span x-text="courseSearch"></span>" tidak ditemukan.
                                            </div>
                                        </template>

                                        <template x-for="course in filteredCourses" :key="course.id">
                                            <button 
                                                type="button"
                                                @click="selectCourse(course.id)"
                                                class="w-full flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg text-left text-xs transition-colors"
                                                :class="String(activeCourseId) === String(course.id) ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#263445] hover:bg-[#F5F8FC]'"
                                            >
                                                <div class="min-w-0">
                                                    <p class="truncate" x-text="course.title"></p>
                                                    <p x-show="course.description" class="text-[10px] text-[#718096] truncate" x-text="course.description"></p>
                                                </div>
                                                <svg x-show="String(activeCourseId) === String(course.id)" class="w-4 h-4 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Dynamic Dropdown Pertemuan -->
                            <div class="relative w-full sm:w-64" @click.outside="meetingDropdownOpen = false">
                                <button 
                                    type="button"
                                    @click="toggleMeetingDropdown()"
                                    :disabled="!activeCourseId"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-1.5 border rounded-xl text-xs transition-all text-left disabled:opacity-55 disabled:cursor-not-allowed"
                                    :class="meetingDropdownOpen ? 'border-[#438FCE] ring-2 ring-[#438FCE]/15 bg-white' : 'bg-[#F8FAFD] hover:bg-white border-[#E5EAF0] hover:border-[#438FCE]/60'"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <!-- Loading Spinner when fetching meetings -->
                                        <template x-if="loadingMeetings">
                                            <svg class="w-3.5 h-3.5 text-[#438FCE] animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <template x-if="!loadingMeetings">
                                            <svg class="w-3.5 h-3.5 text-[#419D98] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                            </svg>
                                        </template>
                                        <span 
                                            class="truncate font-semibold" 
                                            :class="selectedMeeting ? 'text-[#263445]' : 'text-[#718096]'"
                                            x-text="meetingTriggerLabel"
                                        ></span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-[#718096] shrink-0 transition-transform duration-150" :class="meetingDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Popover Daftar Pertemuan Kelas Terpilih -->
                                <div 
                                    x-show="meetingDropdownOpen" 
                                    x-transition.opacity.duration.150ms
                                    class="absolute left-0 mt-1.5 w-full sm:w-80 bg-white rounded-xl shadow-lg border border-[#E5EAF0] z-20 overflow-hidden"
                                    x-cloak
                                >
                                    <!-- Quick filter jika pertemuan cukup banyak -->
                                    <div x-show="meetings.length > 5" class="p-2 border-b border-[#E5EAF0] bg-[#F8FAFD]">
                                        <input 
                                            x-model="meetingSearch" 
                                            type="text" 
                                            placeholder="Filter pertemuan atau judul materi..." 
                                            class="w-full px-2.5 py-1.5 text-xs bg-white border border-[#E5EAF0] rounded-lg focus:border-[#438FCE] focus:ring-1 focus:ring-[#438FCE] text-[#263445]"
                                        />
                                    </div>

                                    <div class="max-h-64 overflow-y-auto p-1.5 space-y-1">
                                        <!-- Loading State -->
                                        <template x-if="loadingMeetings">
                                            <div class="p-4 text-center text-xs text-[#718096] flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4 text-[#438FCE] animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span>Memuat daftar pertemuan...</span>
                                            </div>
                                        </template>

                                        <!-- Error State -->
                                        <template x-if="!loadingMeetings && meetingsError">
                                            <div class="p-3 text-center text-xs text-[#C54B4B] bg-[#FDF0F0] rounded-lg space-y-2">
                                                <p x-text="meetingsError"></p>
                                                <button type="button" @click="fetchMeetings(activeCourseId)" class="px-2.5 py-1 bg-white border border-[#F8D5D5] text-[#C54B4B] rounded-lg font-medium hover:bg-red-50">
                                                    Coba Lagi
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Empty State -->
                                        <template x-if="!loadingMeetings && !meetingsError && meetings.length === 0">
                                            <div class="p-4 text-center text-xs text-[#718096] space-y-1">
                                                <p class="font-semibold text-[#263445]">Belum Ada Pertemuan</p>
                                                <p class="text-[11px]">Kelas ini belum memiliki pertemuan atau materi yang tersedia.</p>
                                            </div>
                                        </template>

                                        <!-- Opsi Diskusi Umum Kelas + Daftar Pertemuan -->
                                        <template x-if="!loadingMeetings && !meetingsError && meetings.length > 0">
                                            <div class="space-y-0.5">
                                                <button 
                                                    type="button"
                                                    @click="selectMeeting('')"
                                                    class="w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-left text-xs transition-colors"
                                                    :class="!activeMeetingId ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445]'"
                                                >
                                                    <span>Umum / Seluruh Topik Kelas Ini</span>
                                                    <svg x-show="!activeMeetingId" class="w-4 h-4 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>

                                                <div class="my-1 border-t border-[#E5EAF0]"></div>

                                                <template x-for="meeting in filteredMeetings" :key="meeting.id">
                                                    <button 
                                                        type="button"
                                                        @click="selectMeeting(meeting.id)"
                                                        class="w-full flex items-start justify-between gap-2 px-2.5 py-2 rounded-lg text-left text-xs transition-colors"
                                                        :class="String(activeMeetingId) === String(meeting.id) ? 'bg-[#DFEAF9] text-[#2E6C9E]' : 'text-[#263445] hover:bg-[#F5F8FC]'"
                                                    >
                                                        <div class="min-w-0 flex-1 space-y-0.5">
                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-[#F5F8FC] text-[#438FCE] border border-[#E5EAF0]" x-text="meeting.meeting_label"></span>
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="typeBadgeClass(meeting.type)" x-text="meeting.type_label"></span>
                                                            </div>
                                                            <p class="font-semibold truncate" x-text="meeting.title"></p>
                                                        </div>
                                                        <svg x-show="String(activeMeetingId) === String(meeting.id)" class="w-4 h-4 text-[#438FCE] shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                </template>

                                                <template x-if="filteredMeetings.length === 0">
                                                    <div class="p-3 text-center text-xs text-[#718096]">
                                                        Pertemuan tidak ditemukan.
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol selesai edit jika berasal dari autoContext -->
                            <button 
                                x-show="autoContext && isEditingContext && selectedCourse" 
                                type="button" 
                                @click="isEditingContext = false"
                                class="text-xs px-2.5 py-1.5 rounded-xl border border-[#E5EAF0] text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] font-medium"
                            >
                                Selesai
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Right: Mode Belajar -->
                <div class="flex items-center gap-2 shrink-0">
                    <div class="flex items-center gap-1.5 text-xs bg-[#F8FAFD] border border-[#E5EAF0] rounded-xl px-2.5 py-1.5">
                        <svg class="w-3.5 h-3.5 text-[#718096] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span class="text-[#718096] font-medium hidden lg:inline">Mode:</span>
                        <select 
                            x-model="studyMode" 
                            class="bg-transparent border-0 p-0 pr-6 text-xs font-semibold text-[#263445] focus:ring-0 cursor-pointer"
                        >
                            <option value="tanya_jawab">Tanya Jawab</option>
                            <option value="penjelasan_sederhana">Penjelasan Sederhana</option>
                            <option value="ringkasan">Ringkasan Materi</option>
                            <option value="latihan_soal">Latihan Soal</option>
                            <option value="persiapan_ujian">Persiapan Ujian</option>
                        </select>
                    </div>

                    <template x-if="studyMode === 'latihan_soal'">
                        <select 
                            x-model="difficulty" 
                            class="text-xs font-semibold border-[#E5EAF0] bg-[#F8FAFD] text-[#263445] rounded-xl py-1.5 px-2.5 focus:ring-[#438FCE] focus:border-[#438FCE]"
                        >
                            <option value="dasar">Dasar</option>
                            <option value="menengah">Menengah</option>
                            <option value="lanjutan">Lanjutan</option>
                        </select>
                    </template>
                </div>
            </div>

            <!-- Active Context Summary Strip & Error Feedback -->
            <div x-show="contextError || (selectedCourse && (selectedMeeting || (!loadingMeetings && meetings.length === 0)))" class="px-3 lg:px-4 py-1.5 lg:py-2 bg-[#FAFBFD] border-b border-[#E5EAF0] flex flex-wrap items-center justify-between gap-1.5 lg:gap-2 text-[11px] lg:text-xs" x-cloak>
                <template x-if="contextError">
                    <div class="flex items-center justify-between w-full gap-2 text-[#C54B4B] bg-[#FDF0F0] border border-[#F8D5D5] px-3 py-1.5 rounded-lg">
                        <span x-text="contextError"></span>
                        <button type="button" @click="contextError = ''" class="font-bold hover:opacity-75">&times;</button>
                    </div>
                </template>

                <template x-if="!contextError && selectedCourse && selectedMeeting">
                    <div class="flex items-center justify-between w-full gap-2 flex-wrap">
                        <div class="flex items-center gap-2 text-[#263445] min-w-0">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#DFEAF9] text-[#438FCE] border border-[#C3DAF5] shrink-0">Konteks Aktif</span>
                            <span class="font-semibold truncate" x-text="selectedCourse.title"></span>
                            <span class="text-[#718096]">&rsaquo;</span>
                            <span class="text-[#718096] truncate" x-text="selectedMeeting.display_label"></span>
                        </div>
                        <span 
                            x-show="!selectedMeeting.has_text_content" 
                            class="text-[11px] text-[#B8873E] bg-[#FDF5EB] border border-[#F7E1C8] px-2 py-0.5 rounded-md shrink-0"
                        >
                            Lampiran/Video tanpa teks modul
                        </span>
                    </div>
                </template>

                <template x-if="!contextError && selectedCourse && !selectedMeeting && !loadingMeetings && meetings.length === 0">
                    <div class="flex items-center gap-2 text-[#718096]">
                        <span class="w-2 h-2 rounded-full bg-[#B8873E] shrink-0"></span>
                        <span>Kelas <strong class="text-[#263445]" x-text="selectedCourse.title"></strong> belum memiliki pertemuan/materi. Pertanyaan tetap dapat dijawab secara umum.</span>
                    </div>
                </template>
            </div>

            <!-- Messages Stream Area -->
            <div 
                x-ref="messagesContainer" 
                @scroll.passive="onChatScroll()"
                class="flex-1 overflow-y-auto px-2.5 sm:px-4 lg:px-5 py-3.5 lg:py-5 bg-[#F8FAFD] relative"
            >
                <div class="max-w-4xl mx-auto space-y-4 lg:space-y-5">
                    <!-- Empty State -->
                    <template x-if="messages.length === 0">
                        <div class="max-w-xl mx-auto py-4 sm:py-8 lg:py-12 text-center space-y-4 lg:space-y-6">
                            <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl lg:rounded-2xl bg-[#DFEAF9] text-[#438FCE] flex items-center justify-center mx-auto border border-[#C3DAF5]">
                                <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>

                            <div>
                                <h3 class="text-base lg:text-lg font-bold text-[#263445]">Mulai Belajar dengan AI Tutor</h3>
                                <template x-if="selectedCourse && selectedMeeting">
                                    <p class="text-xs sm:text-sm text-[#718096] mt-1.5 max-w-md mx-auto">
                                        Fokus belajar aktif pada <strong class="text-[#263445]" x-text="selectedCourse.title"></strong> &mdash; <strong class="text-[#438FCE]" x-text="selectedMeeting.display_label"></strong>.
                                    </p>
                                </template>
                                <template x-if="selectedCourse && !selectedMeeting">
                                    <p class="text-xs sm:text-sm text-[#718096] mt-1.5 max-w-md mx-auto">
                                        Kelas <strong class="text-[#263445]" x-text="selectedCourse.title"></strong> aktif. Pilih pertemuan di atas untuk membahas materi spesifik, atau langsung ajukan pertanyaan.
                                    </p>
                                </template>
                                <template x-if="!selectedCourse">
                                    <p class="text-xs sm:text-sm text-[#718096] mt-1.5 max-w-md mx-auto">
                                        <span x-show="courses.length > 0">Pilih kelas dan pertemuan terlebih dahulu pada menu di atas agar AI menjawab sesuai materi pembelajaranmu.</span>
                                        <span x-show="courses.length === 0">Anda belum bergabung di kelas manapun. Gabung ke kelas untuk membahas materi bersama AI Tutor, atau ajukan pertanyaan umum.</span>
                                    </p>
                                </template>
                            </div>

                            <!-- 3 Quick-Action Prompts -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3 text-left pt-1 lg:pt-2">
                                <button 
                                    type="button"
                                    @click="sendQuickAction('jelaskan')" 
                                    class="p-2.5 sm:p-3.5 bg-white border border-[#E5EAF0] hover:border-[#438FCE]/60 hover:bg-white rounded-xl transition-all shadow-2xs group flex flex-col justify-between"
                                >
                                    <div class="w-7 h-7 rounded-lg bg-[#FCEAD9] text-[#B8873E] flex items-center justify-center mb-1.5 sm:mb-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-[#263445] group-hover:text-[#438FCE]">Jelaskan Konsep</h4>
                                        <p class="text-[11px] text-[#718096] mt-0.5 leading-snug">Penjelasan dengan bahasa sederhana dan contoh nyata.</p>
                                    </div>
                                </button>

                                <button 
                                    type="button"
                                    @click="sendQuickAction('ringkasan')" 
                                    class="p-2.5 sm:p-3.5 bg-white border border-[#E5EAF0] hover:border-[#438FCE]/60 hover:bg-white rounded-xl transition-all shadow-2xs group flex flex-col justify-between"
                                >
                                    <div class="w-7 h-7 rounded-lg bg-[#DFEAF9] text-[#438FCE] flex items-center justify-center mb-1.5 sm:mb-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-[#263445] group-hover:text-[#438FCE]">Ringkas Materi</h4>
                                        <p class="text-[11px] text-[#718096] mt-0.5 leading-snug">Poin-poin penting, istilah kunci, dan kesimpulan.</p>
                                    </div>
                                </button>

                                <button 
                                    type="button"
                                    @click="sendQuickAction('kuis')" 
                                    class="p-2.5 sm:p-3.5 bg-white border border-[#E5EAF0] hover:border-[#438FCE]/60 hover:bg-white rounded-xl transition-all shadow-2xs group flex flex-col justify-between"
                                >
                                    <div class="w-7 h-7 rounded-lg bg-[#E0F0E6] text-[#32865B] flex items-center justify-center mb-1.5 sm:mb-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-[#263445] group-hover:text-[#438FCE]">Latihan Kuis</h4>
                                        <p class="text-[11px] text-[#718096] mt-0.5 leading-snug">Uji pemahaman dengan 1 soal interaktif.</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Messages Stream Loop -->
                    <template x-for="(msg, index) in messages" :key="msg.id || index">
                        <div>
                            <!-- User Message Bubble (Proporsional, Sisi Kanan, Fit Content tanpa spasi indentasi) -->
                            <template x-if="msg.role === 'user'">
                                <div class="flex flex-col items-end">
                                    <div class="w-fit max-w-[88%] sm:max-w-[75%] lg:max-w-[72%] bg-[#438FCE] text-white text-[13px] lg:text-sm px-3 lg:px-4 py-1.5 lg:py-2 rounded-2xl rounded-br-sm leading-relaxed shadow-2xs text-left"><span class="whitespace-pre-wrap break-words" x-text="msg.content"></span></div>
                                    <span class="text-[10px] text-[#718096] mt-1 px-1" x-text="msg.created_at || 'Baru saja'"></span>
                                </div>
                            </template>

                            <!-- Assistant (AI Tutor) Message (Minimalis, Bersih, Nyaman untuk Bacaan Panjang) -->
                            <template x-if="msg.role === 'assistant'">
                                <div class="flex items-start gap-2 sm:gap-2.5 lg:gap-3">
                                    <div class="w-6 h-6 lg:w-7 lg:h-7 rounded-lg bg-[#DFEAF9] text-[#438FCE] border border-[#C3DAF5] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 lg:w-3.5 lg:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0 space-y-1.5 lg:space-y-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] lg:text-xs font-semibold text-[#263445]">Eduva AI Tutor</span>
                                            <span class="text-[10px] text-[#718096]" x-text="msg.created_at || 'Baru saja'"></span>
                                        </div>

                                        <!-- Rendered Markdown & Highlighted Code -->
                                        <div 
                                            class="ai-markdown" 
                                            @click="handleMarkdownClick($event)" 
                                            x-html="renderMarkdown(msg.content)"
                                        ></div>

                                        <!-- Sources Citation Pill (Subtle) -->
                                        <template x-if="msg.sources && msg.sources.length > 0">
                                            <div class="pt-1 flex flex-wrap items-center gap-1.5">
                                                <template x-for="src in msg.sources" :key="src.title">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-[#E5EAF0] text-[11px] text-[#718096]">
                                                        <svg class="w-3 h-3 text-[#438FCE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                        <span class="font-medium text-[#263445]" x-text="src.title"></span>
                                                        <span x-show="src.topic">&bull; <span x-text="src.topic"></span></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Subtle Message Footer Actions -->
                                        <div class="flex items-center gap-3 pt-0.5 text-[11px] text-[#718096]">
                                            <button 
                                                type="button"
                                                @click="copyMessage(msg.content, msg.id || index)" 
                                                class="inline-flex items-center gap-1 transition-colors py-0.5 px-1.5 rounded-md hover:bg-white hover:text-[#263445]"
                                                :class="copiedMessageKey === (msg.id || index) ? 'text-[#32865B] font-medium' : ''"
                                                title="Salin Jawaban AI"
                                            >
                                                <svg x-show="copiedMessageKey !== (msg.id || index)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                <svg x-show="copiedMessageKey === (msg.id || index)" x-cloak class="w-3.5 h-3.5 text-[#32865B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                <span x-text="copiedMessageKey === (msg.id || index) ? 'Tersalin' : 'Salin'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- AI Typing / Thinking Indicator -->
                    <div x-show="isLoading" class="flex items-start gap-2 sm:gap-2.5 lg:gap-3" x-cloak>
                        <div class="w-6 h-6 lg:w-7 lg:h-7 rounded-lg bg-[#DFEAF9] text-[#438FCE] border border-[#C3DAF5] flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3 lg:w-3.5 lg:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="pt-1 flex items-center gap-2 text-[11px] lg:text-xs text-[#718096]">
                            <div class="flex gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#438FCE] animate-bounce"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-[#438FCE] animate-bounce [animation-delay:0.2s]"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-[#438FCE] animate-bounce [animation-delay:0.4s]"></span>
                            </div>
                            <span>Eduva AI sedang menyusun jawaban...</span>
                        </div>
                    </div>

                    <!-- Error Alert Box -->
                    <div x-show="errorMessage" class="p-3 rounded-xl bg-[#FDF0F0] border border-[#F8D5D5] text-[#C54B4B] text-xs flex items-center justify-between gap-3" x-cloak>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#C54B4B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span x-text="errorMessage"></span>
                        </div>
                        <button @click="errorMessage = ''" class="text-[#C54B4B] hover:opacity-75 font-bold">&times;</button>
                    </div>
                </div>

                <!-- Floating "Jump to latest message" button when scrolled up reading older messages -->
                <button 
                    x-show="hasUnreadBelow" 
                    x-cloak
                    x-transition
                    type="button"
                    @click="scrollToBottom()"
                    class="sticky bottom-3 left-1/2 -translate-x-1/2 px-3 py-1.5 rounded-full bg-white border border-[#E5EAF0] shadow-md text-xs font-semibold text-[#438FCE] hover:bg-[#DFEAF9]/40 inline-flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                    <span>Pesan baru di bawah</span>
                </button>
            </div>

            <!-- Composer Input Box (Centered, Auto-Grow, Compact Rounded) -->
            <div class="p-2.5 sm:px-4 lg:px-5 sm:py-3 lg:py-3.5 bg-white border-t border-[#E5EAF0] shrink-0">
                <form @submit.prevent="sendMessage" class="max-w-4xl mx-auto space-y-1.5">
                    <div class="flex items-end gap-2 bg-[#F8FAFD] border border-[#E5EAF0] rounded-xl lg:rounded-2xl px-2.5 lg:px-3 py-1.5 lg:py-2 focus-within:bg-white focus-within:border-[#438FCE] focus-within:ring-2 focus-within:ring-[#438FCE]/15 transition-all">
                        <textarea 
                            x-ref="messageInput"
                            x-model="userInput" 
                            @input="autoResize($el)"
                            @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                            rows="1" 
                            class="flex-1 bg-transparent border-0 focus:ring-0 text-[13px] lg:text-sm py-1 px-1 text-[#263445] resize-none min-h-[24px] max-h-36 overflow-y-auto placeholder:text-[#718096] leading-relaxed" 
                            placeholder="Tanyakan materi, minta contoh, atau penjelasan kode..."
                            :disabled="isLoading"
                        ></textarea>

                        <button 
                            type="submit" 
                            :disabled="isLoading || !userInput.trim()"
                            class="w-7 h-7 lg:w-8 lg:h-8 rounded-lg lg:rounded-xl bg-[#438FCE] text-white flex items-center justify-center shrink-0 hover:bg-[#387EB8] transition-all disabled:opacity-35 disabled:cursor-not-allowed shadow-2xs"
                            title="Kirim Pesan"
                        >
                            <svg x-show="!isLoading" class="w-3.5 h-3.5 lg:w-4 lg:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            <svg x-show="isLoading" class="w-3.5 h-3.5 lg:w-4 lg:h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-[10px] lg:text-[11px] text-[#718096] px-1">
                        <span class="truncate">AI mengutamakan materi dari kelas &amp; pertemuan yang sedang dipilih.</span>
                        <span class="hidden sm:inline shrink-0">Enter kirim &bull; Shift+Enter baris baru</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rename Modal -->
        <div 
            x-show="renameModalOpen" 
            x-transition.opacity 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            x-cloak
        >
            <div @click.away="renameModalOpen = false" class="bg-white rounded-2xl max-w-sm w-full p-5 space-y-4 shadow-xl border border-[#E5EAF0]">
                <h3 class="font-bold text-[#263445] text-sm">Ubah Judul Percakapan</h3>
                <input 
                    type="text" 
                    x-model="renameTitle" 
                    class="w-full text-xs rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] p-2.5 text-[#263445]" 
                    maxlength="100"
                />
                <div class="flex justify-end gap-2 text-xs">
                    <button 
                        type="button" 
                        @click="renameModalOpen = false" 
                        class="px-3 py-1.5 rounded-lg border border-[#E5EAF0] text-[#718096] hover:bg-[#F5F8FC]"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="saveRename()" 
                        class="px-3.5 py-1.5 rounded-lg bg-[#438FCE] text-white font-medium hover:bg-[#387EB8]"
                    >
                        Simpan
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div 
            x-show="toastMessage" 
            x-transition 
            class="fixed bottom-6 right-6 z-50 bg-[#263445] text-white text-xs px-3.5 py-2 rounded-xl shadow-lg flex items-center gap-2"
            x-cloak
        >
            <svg class="w-3.5 h-3.5 text-[#E0F0E6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span x-text="toastMessage"></span>
        </div>
    </div>

    <!-- Highlight.js, DOMPurify, and Marked.js for safe & rich Markdown/Code rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.10.0/styles/github-dark.min.css">
    <script src="https://cdn.jsdelivr.net/npm/highlight.js@11.10.0/lib/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiTutorApp', (config) => ({
                historyOpen: false,
                activeConvId: config.activeConvId,
                activeCourseId: config.activeCourseId ? String(config.activeCourseId) : '',
                activeMeetingId: config.activeMeetingId ? String(config.activeMeetingId) : '',
                autoContext: Boolean(config.autoContext),
                isEditingContext: false,
                courses: config.courses || [],
                meetings: config.initialMeetings || [],
                courseDropdownOpen: false,
                meetingDropdownOpen: false,
                courseSearch: '',
                meetingSearch: '',
                loadingMeetings: false,
                meetingsError: '',
                contextError: config.initialContextError || '',
                studyMode: config.studyMode || 'tanya_jawab',
                difficulty: config.difficulty || 'menengah',
                chatEndpoint: config.chatEndpoint,
                meetingsEndpointTemplate: config.meetingsEndpointTemplate,
                csrfToken: config.csrfToken,
                messages: config.initialMessages || [],
                userInput: '',
                isLoading: false,
                errorMessage: '',
                toastMessage: '',
                hasUnreadBelow: false,
                copiedMessageKey: null,
                copyFeedbackTimer: null,
                renameModalOpen: false,
                renameConvId: null,
                renameTitle: '',

                init() {
                    this.scrollToBottom();
                },

                get selectedCourse() {
                    if (!this.activeCourseId) return null;
                    return this.courses.find(c => String(c.id) === String(this.activeCourseId)) || null;
                },

                get selectedMeeting() {
                    if (!this.activeMeetingId) return null;
                    return this.meetings.find(m => String(m.id) === String(this.activeMeetingId)) || null;
                },

                get filteredCourses() {
                    const q = this.courseSearch.trim().toLowerCase();
                    if (!q) return this.courses;
                    return this.courses.filter(c => (c.title || '').toLowerCase().includes(q));
                },

                get filteredMeetings() {
                    const q = this.meetingSearch.trim().toLowerCase();
                    if (!q) return this.meetings;
                    return this.meetings.filter(m =>
                        (m.display_label || '').toLowerCase().includes(q) ||
                        (m.title || '').toLowerCase().includes(q) ||
                        (m.topic || '').toLowerCase().includes(q)
                    );
                },

                get meetingTriggerLabel() {
                    if (!this.activeCourseId) return 'Pilih kelas dulu';
                    if (this.loadingMeetings) return 'Memuat pertemuan...';
                    if (this.meetingsError) return 'Gagal memuat pertemuan';
                    if (this.meetings.length === 0) return 'Belum ada pertemuan';
                    if (this.selectedMeeting) return this.selectedMeeting.display_label;
                    return 'Pilih Pertemuan...';
                },

                typeBadgeClass(type) {
                    switch (type) {
                        case 'file': return 'bg-[#E0F0E6] text-[#32865B]';
                        case 'page': return 'bg-[#DDF1F0] text-[#419D98]';
                        case 'video':
                        case 'label': return 'bg-[#FCEAD9] text-[#B8873E]';
                        default: return 'bg-[#DFEAF9] text-[#438FCE]';
                    }
                },

                toggleCourseDropdown() {
                    this.courseDropdownOpen = !this.courseDropdownOpen;
                    this.meetingDropdownOpen = false;
                    if (this.courseDropdownOpen) {
                        this.$nextTick(() => this.$refs.courseSearchInput?.focus());
                    }
                },

                toggleMeetingDropdown() {
                    if (!this.activeCourseId) return;
                    this.meetingDropdownOpen = !this.meetingDropdownOpen;
                    this.courseDropdownOpen = false;
                },

                async selectCourse(courseId) {
                    const nextId = String(courseId);
                    this.courseDropdownOpen = false;
                    this.courseSearch = '';

                    if (this.activeCourseId === nextId) {
                        return;
                    }

                    // Reset pilihan pertemuan saat kelas berubah lalu muat pertemuan kelas baru
                    this.activeCourseId = nextId;
                    this.activeMeetingId = '';
                    this.meetingSearch = '';
                    this.meetings = [];
                    this.contextError = '';

                    await this.fetchMeetings(nextId);
                },

                selectMeeting(meetingId) {
                    this.activeMeetingId = meetingId ? String(meetingId) : '';
                    this.meetingDropdownOpen = false;
                    this.meetingSearch = '';
                    this.contextError = '';
                },

                async fetchMeetings(courseId, preserveMeeting = false) {
                    if (!courseId) return;

                    this.loadingMeetings = true;
                    this.meetingsError = '';

                    try {
                        const url = this.meetingsEndpointTemplate.replace('__COURSE_ID__', encodeURIComponent(courseId));
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.error || 'Gagal mengambil daftar pertemuan.');
                        }

                        if (String(this.activeCourseId) === String(courseId)) {
                            this.meetings = data.meetings || [];
                            if (!preserveMeeting) {
                                this.activeMeetingId = '';
                            }
                        }
                    } catch (err) {
                        console.error('Fetch meetings error:', err);
                        this.meetingsError = err.message || 'Gagal memuat daftar pertemuan.';
                    } finally {
                        this.loadingMeetings = false;
                    }
                },

                isNearBottom() {
                    const container = this.$refs.messagesContainer;
                    if (!container) return true;
                    return (container.scrollHeight - container.scrollTop - container.clientHeight) <= 140;
                },

                onChatScroll() {
                    if (this.isNearBottom()) {
                        this.hasUnreadBelow = false;
                    }
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                            this.hasUnreadBelow = false;
                        }
                    });
                },

                autoResize(el) {
                    if (!el) return;
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 144) + 'px';
                },

                resetTextareaHeight() {
                    this.$nextTick(() => {
                        const el = this.$refs.messageInput;
                        if (el) {
                            el.style.height = 'auto';
                        }
                    });
                },

                sendQuickAction(actionType) {
                    if (actionType === 'jelaskan') {
                        this.studyMode = 'penjelasan_sederhana';
                        this.userInput = 'Tolong jelaskan konsep dasar materi pada pertemuan ini dengan analogi sederhana yang mudah dimengerti beserta contohnya.';
                    } else if (actionType === 'ringkasan') {
                        this.studyMode = 'ringkasan';
                        this.userInput = 'Tolong buatkan ringkasan poin-poin paling penting beserta kesimpulan dari materi pertemuan ini.';
                    } else if (actionType === 'kuis') {
                        this.studyMode = 'latihan_soal';
                        this.userInput = 'Berikan 1 pertanyaan latihan interaktif untuk menguji pemahaman saya tentang materi pertemuan ini.';
                    }
                    this.sendMessage();
                },

                escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                },

                fallbackSanitizeHtml(html) {
                    return String(html)
                        .replace(/<\s*(script|iframe|object|embed|form|style)[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/gi, '')
                        .replace(/<\s*(script|iframe|object|embed|form|style)[^>]*\/?>/gi, '')
                        .replace(/\son[a-z]+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)/gi, '')
                        .replace(/javascript\s*:/gi, 'blocked:');
                },

                sanitizeHtml(html) {
                    if (window.DOMPurify) {
                        return window.DOMPurify.sanitize(html, {
                            USE_PROFILES: { html: true },
                            ADD_TAGS: ['button'],
                            ADD_ATTR: ['data-action', 'data-code', 'data-lang', 'type'],
                            FORBID_TAGS: ['script', 'iframe', 'object', 'embed', 'form', 'style'],
                            FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover', 'onfocus'],
                        });
                    }
                    return this.fallbackSanitizeHtml(html);
                },

                renderCodeBlockHtml(rawCode, rawLang) {
                    const lang = String(rawLang || '').trim().toLowerCase();
                    const label = lang ? lang.toUpperCase() : 'KODE';
                    let highlighted = this.escapeHtml(rawCode);

                    if (window.hljs) {
                        try {
                            if (lang && window.hljs.getLanguage(lang)) {
                                highlighted = window.hljs.highlight(rawCode, { language: lang }).value;
                            } else {
                                highlighted = window.hljs.highlightAuto(rawCode).value;
                            }
                        } catch (e) {
                            highlighted = this.escapeHtml(rawCode);
                        }
                    }

                    const encodedCode = this.escapeHtml(encodeURIComponent(rawCode));
                    const isPreviewableHtml = ['html', 'htm', 'svg'].includes(lang);
                    const previewBtn = isPreviewableHtml
                        ? `<button type="button" data-action="preview-html" data-code="${encodedCode}" class="px-2 py-0.5 rounded bg-slate-700 hover:bg-slate-600 text-[11px] font-medium text-slate-200 transition-colors">Preview</button>`
                        : '';

                    const previewArea = isPreviewableHtml
                        ? `<div class="ai-code-preview hidden bg-white border-t border-slate-700 p-3"></div>`
                        : '';

                    return `<div class="ai-code-block">`
                        + `<div class="ai-code-header">`
                        + `<span class="font-mono text-[11px] font-semibold tracking-wider text-slate-300">${this.escapeHtml(label)}</span>`
                        + `<div class="flex items-center gap-1.5">${previewBtn}<button type="button" data-action="copy-code" data-code="${encodedCode}" class="px-2 py-0.5 rounded bg-slate-700 hover:bg-slate-600 text-[11px] font-medium text-slate-200 transition-colors">Salin Kode</button></div>`
                        + `</div>`
                        + `<pre><code class="hljs ${lang ? 'language-' + this.escapeHtml(lang) : ''}">${highlighted}</code></pre>`
                        + `${previewArea}`
                        + `</div>`;
                },

                renderMarkdown(text) {
                    if (!text) return '';
                    const self = this;

                    if (window.marked) {
                        try {
                            const renderer = new window.marked.Renderer();
                            const origTable = window.marked.Renderer.prototype.table;

                            renderer.code = function (codeOrToken, infostring) {
                                const codeText = typeof codeOrToken === 'object' && codeOrToken !== null
                                    ? (codeOrToken.text || '')
                                    : (codeOrToken || '');
                                const lang = typeof codeOrToken === 'object' && codeOrToken !== null
                                    ? (codeOrToken.lang || '')
                                    : (infostring || '');
                                return self.renderCodeBlockHtml(codeText, lang);
                            };

                            renderer.table = function (...args) {
                                return `<div class="ai-table-wrap">${origTable.apply(this, args)}</div>`;
                            };

                            const parsed = window.marked.parse(text, {
                                breaks: true,
                                gfm: true,
                                renderer: renderer,
                            });

                            return this.sanitizeHtml(parsed);
                        } catch (e) {
                            console.error('Marked parsing error:', e);
                        }
                    }

                    const fallback = this.escapeHtml(text)
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/`(.*?)`/g, '<code>$1</code>')
                        .replace(/\n/g, '<br>');
                    return this.sanitizeHtml(fallback);
                },

                handleMarkdownClick(event) {
                    const btn = event.target?.closest?.('button[data-action]');
                    if (!btn) return;

                    const action = btn.getAttribute('data-action');
                    const encoded = btn.getAttribute('data-code') || '';
                    let decodedCode = '';
                    try {
                        decodedCode = decodeURIComponent(encoded);
                    } catch (e) {
                        decodedCode = encoded;
                    }

                    if (action === 'copy-code') {
                        navigator.clipboard.writeText(decodedCode).then(() => {
                            const original = btn.textContent;
                            btn.textContent = 'Tersalin!';
                            btn.classList.add('text-emerald-300');
                            this.showToast('Kode berhasil disalin');
                            setTimeout(() => {
                                btn.textContent = original || 'Salin Kode';
                                btn.classList.remove('text-emerald-300');
                            }, 1800);
                        }).catch(() => {
                            this.showToast('Gagal menyalin kode');
                        });
                    } else if (action === 'preview-html') {
                        const block = btn.closest('.ai-code-block');
                        const previewBox = block?.querySelector('.ai-code-preview');
                        if (!previewBox) return;

                        const isHidden = previewBox.classList.contains('hidden');
                        if (isHidden) {
                            const safeHtml = this.fallbackSanitizeHtml(decodedCode);
                            previewBox.innerHTML = '';

                            const bar = document.createElement('div');
                            bar.className = 'text-[11px] font-medium text-[#718096] mb-2 flex items-center justify-between';
                            bar.textContent = 'Pratinjau HTML Terisolasi (Sandbox)';

                            const iframe = document.createElement('iframe');
                            iframe.setAttribute('sandbox', 'allow-same-origin');
                            iframe.setAttribute('referrerpolicy', 'no-referrer');
                            iframe.className = 'w-full h-56 rounded-lg border border-[#E5EAF0] bg-white';
                            iframe.srcdoc = `<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{font-family:Inter,system-ui,sans-serif;padding:12px;margin:0;color:#263445;font-size:14px;}</style></head><body>${safeHtml}</body></html>`;

                            previewBox.appendChild(bar);
                            previewBox.appendChild(iframe);
                            previewBox.classList.remove('hidden');
                            btn.textContent = 'Tutup Preview';
                        } else {
                            previewBox.innerHTML = '';
                            previewBox.classList.add('hidden');
                            btn.textContent = 'Preview';
                        }
                    }
                },

                async sendMessage() {
                    const text = this.userInput.trim();
                    if (!text || this.isLoading) return;

                    this.errorMessage = '';
                    this.userInput = '';
                    this.resetTextareaHeight();

                    const optimisticMsg = {
                        id: 'temp-' + Date.now(),
                        role: 'user',
                        content: text,
                        created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    };
                    this.messages.push(optimisticMsg);
                    this.isLoading = true;
                    this.scrollToBottom();

                    try {
                        const res = await fetch(this.chatEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify({
                                message: text,
                                conversation_id: this.activeConvId,
                                course_id: this.activeCourseId ? Number(this.activeCourseId) : null,
                                meeting_id: this.activeMeetingId ? Number(this.activeMeetingId) : null,
                                material_id: this.activeMeetingId ? Number(this.activeMeetingId) : null,
                                study_mode: this.studyMode,
                                difficulty: this.difficulty
                            })
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            throw new Error(data.error || 'Gagal memproses pesan.');
                        }

                        if (!this.activeConvId && data.conversation_id) {
                            this.activeConvId = data.conversation_id;
                            window.history.replaceState({}, '', '?conversation_id=' + data.conversation_id);
                        }

                        const shouldScroll = this.isNearBottom();
                        this.messages.push(data.ai_message);

                        if (shouldScroll) {
                            this.scrollToBottom();
                        } else {
                            this.hasUnreadBelow = true;
                        }
                    } catch (err) {
                        console.error('AI Tutor error:', err);
                        this.errorMessage = err.message || 'Terjadi kendala saat menghubungi AI. Silakan coba kirim ulang.';
                        if (this.isNearBottom()) {
                            this.scrollToBottom();
                        }
                    } finally {
                        this.isLoading = false;
                        this.$refs.messageInput?.focus();
                    }
                },

                copyMessage(text, key) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.copiedMessageKey = key;
                        this.showToast('Jawaban berhasil disalin');
                        if (this.copyFeedbackTimer) {
                            clearTimeout(this.copyFeedbackTimer);
                        }
                        this.copyFeedbackTimer = setTimeout(() => {
                            this.copiedMessageKey = null;
                        }, 2000);
                    }).catch(() => {
                        this.showToast('Gagal menyalin');
                    });
                },

                showToast(msg) {
                    this.toastMessage = msg;
                    setTimeout(() => {
                        this.toastMessage = '';
                    }, 2500);
                },

                openRenameModal(id, title) {
                    this.renameConvId = id;
                    this.renameTitle = title;
                    this.renameModalOpen = true;
                },

                async saveRename() {
                    if (!this.renameTitle.trim() || !this.renameConvId) return;

                    try {
                        const res = await fetch(`/ai-tutor/conversations/${this.renameConvId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify({ title: this.renameTitle })
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.showToast('Judul berhasil diubah');
                            setTimeout(() => window.location.reload(), 400);
                        }
                    } catch (e) {
                        alert('Gagal mengubah judul percakapan.');
                    } finally {
                        this.renameModalOpen = false;
                    }
                }
            }));
        });
    </script>
</x-app-layout>

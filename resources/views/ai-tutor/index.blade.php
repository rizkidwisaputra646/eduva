<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary to-accent flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight">Eduva AI Tutor</h2>
                    <p class="text-xs text-gray-500">Asisten belajar interaktif berbasis materi kurikulum</p>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Model: Gemini Flash Lite (Respon Cepat)
                </span>
            </div>
        </div>
    </x-slot>

    <!-- Main Container -->
    <div 
        x-data="aiTutorApp({
            activeConvId: {{ $activeConversation ? $activeConversation->id : 'null' }},
            activeMaterialId: {{ $activeConversation && $activeConversation->material_id ? $activeConversation->material_id : ($preselectedMaterialId ?: 'null') }},
            activeCourseId: {{ $activeConversation && $activeConversation->course_id ? $activeConversation->course_id : ($preselectedCourseId ?: 'null') }},
            studyMode: '{{ $activeConversation ? $activeConversation->study_mode : 'tanya_jawab' }}',
            difficulty: '{{ $activeConversation ? $activeConversation->difficulty : 'menengah' }}',
            chatEndpoint: '{{ route('ai-tutor.chat') }}',
            csrfToken: '{{ csrf_token() }}',
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
        class="h-[calc(100vh-140px)] flex bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden relative"
    >
        <!-- Sidebar: Conversation History & Tools -->
        <aside 
            :class="historyOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="absolute md:static inset-y-0 left-0 z-20 w-72 bg-gray-50 border-r border-gray-200 flex flex-col transition-transform duration-300 ease-in-out shrink-0"
        >
            <!-- Sidebar Header -->
            <div class="p-3.5 border-b border-gray-200 flex items-center justify-between bg-white">
                <a 
                    href="{{ route('ai-tutor.new') }}" 
                    class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-primary-dark transition-all shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Percakapan Baru
                </a>
                <button 
                    @click="historyOpen = false" 
                    class="md:hidden ml-2 p-2 text-gray-500 hover:bg-gray-100 rounded-lg"
                    title="Tutup Menu"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Conversations List -->
            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                <div class="px-2 py-1 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                    Riwayat Belajar
                </div>

                @forelse($conversations as $conv)
                    <div 
                        class="group relative flex items-center justify-between rounded-xl px-3 py-2.5 text-xs transition-all {{ $activeConversation && $activeConversation->id === $conv->id ? 'bg-indigo-50 text-primary font-semibold shadow-xs' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <a 
                            href="{{ route('ai-tutor.index', ['conversation_id' => $conv->id]) }}" 
                            class="flex-1 min-w-0 pr-2"
                        >
                            <p class="truncate font-medium text-gray-800 {{ $activeConversation && $activeConversation->id === $conv->id ? 'text-primary' : '' }}">
                                {{ $conv->title }}
                            </p>
                            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-gray-400">
                                <span>{{ $conv->created_at->diffForHumans(null, true) }}</span>
                                @if($conv->material)
                                    <span>•</span>
                                    <span class="truncate max-w-[100px]">{{ $conv->material->title }}</span>
                                @endif
                            </div>
                        </a>

                        <!-- Actions (Rename & Delete) -->
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button 
                                @click.prevent="openRenameModal({{ $conv->id }}, '{{ addslashes($conv->title) }}')" 
                                class="p-1 text-gray-400 hover:text-gray-600 rounded" 
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
                                    class="p-1 text-gray-400 hover:text-red-600 rounded" 
                                    title="Hapus"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center text-xs text-gray-400">
                        Belum ada riwayat percakapan. Mulai percakapan pertamamu!
                    </div>
                @endforelse
            </div>

            <!-- Footer: Evaluasi Singkat -->
            @if($evaluations->isNotEmpty())
                <div class="p-3 bg-white border-t border-gray-200">
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-bold text-gray-700">Latihan Terakhir</span>
                        <span class="text-[10px] text-primary font-semibold">{{ $evaluations->where('is_correct', true)->count() }}/{{ $evaluations->count() }} Benar</span>
                    </div>
                    <div class="space-y-1.5 max-h-24 overflow-y-auto text-[11px]">
                        @foreach($evaluations->take(3) as $eval)
                            <div class="flex items-center justify-between p-1.5 rounded-lg {{ $eval->is_correct ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">
                                <span class="truncate max-w-[150px] font-medium">{{ $eval->question }}</span>
                                <span class="font-bold">{{ $eval->score }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>

        <!-- Backdrop for mobile sidebar -->
        <div 
            x-show="historyOpen" 
            @click="historyOpen = false" 
            x-transition.opacity 
            class="fixed inset-0 bg-black/40 z-10 md:hidden"
        ></div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-white">
            <!-- Top Controls Bar -->
            <div class="p-3.5 border-b border-gray-200 bg-white flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <!-- Mobile Hamburger -->
                    <button 
                        @click="historyOpen = !historyOpen" 
                        class="md:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-lg"
                        title="Daftar Percakapan"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </button>

                    <!-- Material Dropdown Selector -->
                    <div class="flex items-center gap-1.5 min-w-0">
                        <svg class="w-4 h-4 text-primary shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <select 
                            x-model="activeMaterialId" 
                            class="text-xs font-medium border-gray-200 rounded-xl py-1.5 pl-3 pr-8 focus:border-primary focus:ring-1 focus:ring-primary truncate max-w-[200px] sm:max-w-[260px] bg-gray-50 hover:bg-gray-100 transition-colors"
                        >
                            <option value="">Semua Materi (Umum)</option>
                            @foreach($courses as $c)
                                <optgroup label="{{ $c->title }}">
                                    @foreach($c->materials as $mat)
                                        <option value="{{ $mat->id }}">
                                            {{ $mat->topic ? '[' . $mat->topic . '] ' : '' }}{{ $mat->title }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Material Info Indicator -->
                    <template x-if="activeMaterialId">
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-primary border border-indigo-100">
                            Materi Terpilih
                        </span>
                    </template>
                </div>

                <!-- Study Mode & Difficulty Selectors -->
                <div class="flex items-center gap-2 self-end lg:self-auto flex-wrap">
                    <!-- Mode Selector -->
                    <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
                        @foreach($studyModes as $key => $mode)
                            <button 
                                type="button"
                                @click="studyMode = '{{ $key }}'" 
                                :class="studyMode === '{{ $key }}' ? 'bg-white text-primary font-semibold shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 text-xs rounded-lg transition-all"
                                title="{{ $mode['desc'] }}"
                            >
                                {{ $mode['name'] }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Difficulty (When Mode is Latihan Soal) -->
                    <template x-if="studyMode === 'latihan_soal'">
                        <select 
                            x-model="difficulty" 
                            class="text-xs font-semibold border-amber-300 bg-amber-50 text-amber-900 rounded-xl py-1 px-2.5 focus:ring-primary focus:border-primary"
                        >
                            @foreach($difficulties as $dKey => $dLabel)
                                <option value="{{ $dKey }}">{{ $dLabel }}</option>
                            @endforeach
                        </select>
                    </template>
                </div>
            </div>

            <!-- Messages Stream Area -->
            <div 
                x-ref="messagesContainer" 
                class="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-50/60 space-y-6"
            >
                <!-- Empty State (When no messages) -->
                <template x-if="messages.length === 0">
                    <div class="max-w-2xl mx-auto py-8 text-center space-y-6">
                        <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-primary to-accent flex items-center justify-center text-white mx-auto shadow-md">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Halo, {{ Auth::user()->name }}!</h3>
                            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                                Saya AI Tutor Eduva. Saya siap membantumu memahami materi, menjawab pertanyaan sulit, merangkum modul, atau menguji pemahamanmu.
                            </p>
                        </div>

                        <!-- Active Mode Banner -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-xs font-medium text-primary">
                            <span>Mode Aktif:</span>
                            <span class="font-bold uppercase tracking-wider" x-text="formatModeName(studyMode)"></span>
                        </div>

                        <!-- Suggested Prompts -->
                        <div class="space-y-2 text-left pt-2">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider text-center">
                                Rekomendasi Pertanyaan
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-w-xl mx-auto">
                                @foreach($suggestedPrompts as $prompt)
                                    <button 
                                        type="button"
                                        @click="sendSuggestedPrompt('{{ addslashes($prompt) }}')" 
                                        class="p-3 text-xs text-left bg-white border border-gray-200 hover:border-primary/50 hover:bg-indigo-50/40 rounded-xl transition-all text-gray-700 shadow-2xs group flex items-start justify-between"
                                    >
                                        <span>{{ $prompt }}</span>
                                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-primary shrink-0 ml-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Message Bubbles Loop -->
                <template x-for="(msg, index) in messages" :key="msg.id || index">
                    <div>
                        <!-- User Message -->
                        <template x-if="msg.role === 'user'">
                            <div class="flex items-start justify-end gap-2.5 w-full my-1">
                                <div class="flex flex-col items-end max-w-[85%] sm:max-w-[80%]">
                                    <div 
                                        class="w-fit h-auto bg-primary text-white text-sm rounded-2xl rounded-tr-none shadow-xs whitespace-pre-wrap text-left leading-relaxed"
                                        style="width: fit-content; max-width: 100%; height: auto; padding: 10px 14px; overflow-wrap: anywhere;"
                                    >
                                        <span x-text="msg.content"></span>
                                    </div>
                                    <span class="text-[10px] text-gray-400 mt-1 mr-1" x-text="msg.created_at || 'Baru saja'"></span>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-primary text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                            </div>
                        </template>

                        <!-- Assistant / AI Message -->
                        <template x-if="msg.role === 'assistant'">
                            <div class="flex items-start gap-3 max-w-3xl mr-auto">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-accent to-primary text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-white border border-gray-200 text-sm text-gray-800 p-4 sm:p-5 rounded-2xl rounded-tl-none shadow-xs leading-relaxed space-y-3">
                                        <!-- Formatted Content -->
                                        <div class="prose prose-sm max-w-none text-gray-800 break-words" x-html="renderMarkdown(msg.content)"></div>

                                        <!-- Sources Citation Box (If available) -->
                                        <template x-if="msg.sources && msg.sources.length > 0">
                                            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-col gap-1.5 bg-gray-50 p-2.5 rounded-xl border border-gray-100 text-xs">
                                                <div class="flex items-center gap-1.5 font-bold text-primary">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    <span>Sumber Materi Rujukan:</span>
                                                </div>
                                                <template x-for="src in msg.sources" :key="src.title">
                                                    <div class="text-[11px] text-gray-600 flex items-center gap-1.5">
                                                        <span class="font-semibold text-gray-800" x-text="src.title"></span>
                                                        <span class="text-gray-400">•</span>
                                                        <span x-text="src.course"></span>
                                                        <template x-if="src.file">
                                                            <span class="px-1.5 py-0.5 rounded bg-gray-200 text-gray-700 text-[9px]" x-text="src.file"></span>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Message Footer Actions -->
                                        <div class="flex items-center justify-between pt-1 border-t border-gray-50 text-[11px] text-gray-400">
                                            <span x-text="msg.created_at || 'Baru saja'"></span>
                                            <div class="flex items-center gap-2">
                                                <button 
                                                    type="button"
                                                    @click="copyText(msg.content)" 
                                                    class="inline-flex items-center gap-1 hover:text-primary transition-colors p-1"
                                                    title="Salin Jawaban"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                    <span>Salin</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Loading State (AI Thinking) -->
                <div x-show="isLoading" class="flex items-start gap-3 max-w-3xl mr-auto" x-cloak>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-accent to-primary text-white flex items-center justify-center shrink-0 shadow-xs animate-pulse">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <div class="bg-white border border-gray-200 text-sm text-gray-600 px-4 py-3 rounded-2xl rounded-tl-none shadow-xs flex items-center gap-2">
                        <div class="flex gap-1">
                            <span class="w-2 h-2 rounded-full bg-primary animate-bounce"></span>
                            <span class="w-2 h-2 rounded-full bg-primary animate-bounce [animation-delay:0.2s]"></span>
                            <span class="w-2 h-2 rounded-full bg-primary animate-bounce [animation-delay:0.4s]"></span>
                        </div>
                        <span class="text-xs font-medium text-gray-500">Eduva AI sedang memproses penjelasan...</span>
                    </div>
                </div>

                <!-- Error Alert Box -->
                <div x-show="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs flex items-start justify-between gap-3 max-w-xl mx-auto" x-cloak>
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span x-text="errorMessage"></span>
                    </div>
                    <button @click="errorMessage = ''" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
                </div>
            </div>

            <!-- Input Box Area -->
            <div class="p-3 sm:p-4 bg-white border-t border-gray-200">
                <form @submit.prevent="sendMessage" class="space-y-2">
                    <div class="flex items-end gap-2 bg-gray-50 border border-gray-200 rounded-2xl p-2 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all">
                        <textarea 
                            x-ref="messageInput"
                            x-model="userInput" 
                            @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                            rows="2" 
                            class="flex-1 bg-transparent border-0 focus:ring-0 text-sm p-2 text-gray-800 resize-none max-h-36 placeholder:text-gray-400 leading-relaxed" 
                            placeholder="Tanyakan apa saja seputar materi pelajaran... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                            :disabled="isLoading"
                        ></textarea>

                        <button 
                            type="submit" 
                            :disabled="isLoading || !userInput.trim()"
                            class="px-4 py-2.5 bg-primary text-white rounded-xl font-medium text-xs hover:bg-primary-dark transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 shrink-0 shadow-xs"
                        >
                            <span x-show="!isLoading">Kirim</span>
                            <span x-show="isLoading" class="flex items-center gap-1" x-cloak>
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Mengetik...</span>
                            </span>
                            <svg x-show="!isLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-400 px-1">
                        <span>Eduva AI Tutor dapat membantu menjelaskan rumus, konsep, kode, hingga latihan soal.</span>
                        <span class="hidden sm:inline">Tekan <strong>Enter ↵</strong> untuk kirim</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rename Modal -->
        <div 
            x-show="renameModalOpen" 
            x-transition.opacity 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            x-cloak
        >
            <div @click.away="renameModalOpen = false" class="bg-white rounded-2xl max-w-sm w-full p-5 space-y-4 shadow-xl">
                <h3 class="font-bold text-gray-900 text-sm">Ubah Judul Percakapan</h3>
                <input 
                    type="text" 
                    x-model="renameTitle" 
                    class="w-full text-xs rounded-xl border-gray-300 focus:border-primary focus:ring-primary p-2.5" 
                    maxlength="100"
                />
                <div class="flex justify-end gap-2 text-xs">
                    <button 
                        type="button" 
                        @click="renameModalOpen = false" 
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="saveRename()" 
                        class="px-3.5 py-1.5 rounded-lg bg-primary text-white font-medium hover:bg-primary-dark"
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
            class="fixed bottom-6 right-6 z-50 bg-gray-900 text-white text-xs px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2"
            x-cloak
        >
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span x-text="toastMessage"></span>
        </div>
    </div>

    <!-- Marked.js for markdown rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiTutorApp', (config) => ({
                historyOpen: false,
                activeConvId: config.activeConvId,
                activeMaterialId: config.activeMaterialId || '',
                activeCourseId: config.activeCourseId || '',
                studyMode: config.studyMode || 'tanya_jawab',
                difficulty: config.difficulty || 'menengah',
                chatEndpoint: config.chatEndpoint,
                csrfToken: config.csrfToken,
                messages: config.initialMessages || [],
                userInput: '',
                isLoading: false,
                errorMessage: '',
                toastMessage: '',
                renameModalOpen: false,
                renameConvId: null,
                renameTitle: '',

                init() {
                    this.scrollToBottom();
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },

                formatModeName(mode) {
                    const map = {
                        'tanya_jawab': 'Tanya Jawab',
                        'penjelasan_sederhana': 'Penjelasan Sederhana',
                        'ringkasan': 'Ringkasan Materi',
                        'latihan_soal': 'Latihan Soal',
                        'persiapan_ujian': 'Persiapan Ujian'
                    };
                    return map[mode] || mode;
                },

                renderMarkdown(text) {
                    if (!text) return '';
                    if (window.marked) {
                        try {
                            return window.marked.parse(text, { breaks: true, gfm: true });
                        } catch (e) {
                            console.error('Marked parsing error:', e);
                        }
                    }
                    // Fallback basic formatter
                    return text
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 py-0.5 rounded text-xs text-red-600 font-mono">$1</code>')
                        .replace(/\n/g, '<br>');
                },

                sendSuggestedPrompt(text) {
                    this.userInput = text;
                    this.sendMessage();
                },

                async sendMessage() {
                    const text = this.userInput.trim();
                    if (!text || this.isLoading) return;

                    this.errorMessage = '';
                    this.userInput = '';

                    // Optimistic User Message
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
                                material_id: this.activeMaterialId || null,
                                course_id: this.activeCourseId || null,
                                study_mode: this.studyMode,
                                difficulty: this.difficulty
                            })
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            throw new Error(data.error || 'Gagal memproses pesan.');
                        }

                        // Update Active Conv ID if new
                        if (!this.activeConvId && data.conversation_id) {
                            this.activeConvId = data.conversation_id;
                            // Optionally update browser URL without full refresh
                            window.history.replaceState({}, '', '?conversation_id=' + data.conversation_id);
                        }

                        // Append AI reply
                        this.messages.push(data.ai_message);
                    } catch (err) {
                        console.error('AI Tutor error:', err);
                        this.errorMessage = err.message || 'Terjadi kesalahan jaringan atau server AI. Silakan coba lagi.';
                    } finally {
                        this.isLoading = false;
                        this.scrollToBottom();
                        this.$refs.messageInput?.focus();
                    }
                },

                copyText(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.showToast('Jawaban berhasil disalin ke clipboard');
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
                            this.showToast('Judul percakapan berhasil diubah');
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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Eduva') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-background text-slate-800 overflow-x-hidden" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-100 transition-transform duration-300 lg:static lg:translate-x-0 flex flex-col shadow-sm lg:shadow-none">
            <div class="flex items-center justify-center h-16 border-b border-gray-50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white font-bold text-xl shadow-md">
                        E
                    </div>
                    <span class="font-bold text-xl tracking-tight text-gray-900">Eduva</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-primary font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors' }}">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>

                <a href="{{ route('kelas.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('kelas.*') || request()->routeIs('quizzes.*') || request()->routeIs('assignments.*') || request()->routeIs('attendances.*') || request()->routeIs('materials.*') ? 'bg-indigo-50 text-primary font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors' }}">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('kelas.*') || request()->routeIs('quizzes.*') || request()->routeIs('assignments.*') || request()->routeIs('attendances.*') || request()->routeIs('materials.*') ? 'text-primary' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    {{ Auth::user()->role === 'guru' ? 'Kelas Saya' : 'Daftar Kelas' }}
                </a>

                <a href="{{ route('ai-tutor.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('ai-tutor.*') ? 'bg-indigo-50 text-primary font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors' }}">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('ai-tutor.*') ? 'text-primary' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    AI Tutor
                </a>

                @if(Auth::user()->role === 'admin')
                <div class="pt-4 mt-2 border-t border-gray-100">
                    <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Admin Panel</p>
                    <a href="{{ route('admin.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('admin.*') ? 'bg-gray-900 text-white font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors' }}">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('admin.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Kelola Pengguna
                    </a>
                </div>
                @endif
            </div>

            <!-- User Menu in Sidebar -->
            <div class="border-t border-gray-100 p-3">
                <div class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 transition-colors">
                    <a href="{{ route('profile.edit') }}" class="flex items-center min-w-0 flex-1 mr-2 group" title="Pengaturan Profil">
                        <div class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate group-hover:text-primary transition-colors">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ Auth::user()->role ?? 'Siswa' }}</p>
                        </div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Keluar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Mobile Topbar -->
            <header class="bg-white border-b border-gray-100 lg:hidden flex items-center justify-between h-16 px-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white font-bold">E</div>
                    <span class="font-bold text-lg text-gray-900">Eduva</span>
                </div>
                <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </header>

            <!-- Page Header -->
            @isset($header)
                <div class="bg-white border-b border-gray-100 py-4 px-6 lg:px-8 hidden lg:block">
                    {{ $header }}
                </div>
            @endisset

            <!-- Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-background p-4 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>

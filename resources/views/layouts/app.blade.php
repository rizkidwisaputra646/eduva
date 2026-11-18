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
<body class="font-sans antialiased bg-[#F5F8FC] text-[#263445] overflow-x-hidden" x-data="{ sidebarOpen: false }">
    <x-toast-notification />
    <div class="flex h-screen overflow-hidden">
        
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#E5EAF0] transition-transform duration-300 lg:static lg:z-auto lg:translate-x-0 flex flex-col shadow-lg lg:shadow-none">
            <div class="flex items-center justify-center h-16 border-b border-[#E5EAF0]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#438FCE] flex items-center justify-center text-white font-bold text-lg shadow-sm">
                        E
                    </div>
                    <span class="font-bold text-xl tracking-tight text-[#263445]">Eduva</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                @if(Auth::user()->role === 'admin')
                    <!-- MENU KHUSUS ADMINISTRATOR (Pusat Pengelolaan & Monitoring) -->
                    <p class="px-4 text-[11px] font-bold text-[#718096] uppercase tracking-wider mb-2">Pusat Administrator</p>

                    <!-- 1. Dashboard Admin -->
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.index') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.index') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard
                    </a>

                    <!-- 2. Manajemen Pengguna -->
                    <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('admin.users.index') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.users.index') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Manajemen Pengguna
                    </a>

                    <!-- 3. Import Pengguna -->
                    <a href="{{ route('admin.users.import') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('admin.users.import*') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.users.import*') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Pengguna
                    </a>

                    <!-- 4. Monitoring Kelas -->
                    <a href="{{ route('admin.courses.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('admin.courses.*') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.courses.*') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Monitoring Kelas
                    </a>

                    <!-- 5. Keamanan Akun -->
                    <a href="{{ route('admin.security') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('admin.security') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.security') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Keamanan Akun
                    </a>

                    <!-- 6. Log Aktivitas -->
                    <a href="{{ route('admin.logs') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('admin.logs') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.logs') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Log Aktivitas
                    </a>

                    <!-- 7. Pengaturan Sistem -->
                    <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('admin.settings') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.settings') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Pengaturan Sistem
                    </a>
                @else
                    <!-- MENU PEMBELAJARAN (Guru & Siswa) -->
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('dashboard') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </a>

                    <a href="{{ route('kelas.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('kelas.*') || request()->routeIs('quizzes.*') || request()->routeIs('assignments.*') || request()->routeIs('attendances.*') || request()->routeIs('materials.*') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('kelas.*') || request()->routeIs('quizzes.*') || request()->routeIs('assignments.*') || request()->routeIs('attendances.*') || request()->routeIs('materials.*') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        {{ Auth::user()->role === 'guru' ? 'Kelas Saya' : 'Daftar Kelas' }}
                    </a>

                    <a href="{{ route('ai-tutor.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl {{ request()->routeIs('ai-tutor.*') ? 'bg-[#DFEAF9] text-[#2E6C9E] font-semibold' : 'text-[#718096] hover:bg-[#F5F8FC] hover:text-[#263445] transition-colors' }}">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('ai-tutor.*') ? 'text-[#438FCE]' : 'text-[#718096]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        AI Tutor
                    </a>
                @endif
            </div>

            <!-- User Menu in Sidebar -->
            <div class="border-t border-[#E5EAF0] p-3">
                <div class="flex items-center justify-between p-2 rounded-xl hover:bg-[#F5F8FC] transition-colors">
                    <a href="{{ route('profile.edit') }}" class="flex items-center min-w-0 flex-1 mr-2 group" title="Pengaturan Profil">
                        <div class="w-9 h-9 rounded-full bg-[#DFEAF9] text-[#438FCE] font-bold text-sm flex items-center justify-center shrink-0 group-hover:bg-[#438FCE] group-hover:text-white transition-colors">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-medium text-[#263445] truncate group-hover:text-[#438FCE] transition-colors">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-[#718096] capitalize">{{ Auth::user()->role ?? 'Siswa' }}</p>
                        </div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 text-[#718096] hover:text-[#C54B4B] hover:bg-[#FDF0F0] rounded-lg transition-colors" title="Keluar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Mobile Topbar -->
            <header class="bg-white border-b border-[#E5EAF0] lg:hidden flex items-center justify-between h-16 px-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#438FCE] flex items-center justify-center text-white font-bold">E</div>
                    <span class="font-bold text-lg text-[#263445]">Eduva</span>
                </div>
                <button @click="sidebarOpen = true" class="text-[#718096] hover:text-[#263445] focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </header>

            <!-- Page Header -->
            @isset($header)
                <div class="bg-white border-b border-[#E5EAF0] py-4 px-6 lg:px-8 hidden lg:block">
                    {{ $header }}
                </div>
            @endisset

            <!-- Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#F5F8FC] p-4 lg:p-8 relative z-0">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>

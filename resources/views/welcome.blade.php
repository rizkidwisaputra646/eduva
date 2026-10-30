<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eduva - Sistem Manajemen Pembelajaran Cerdas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-white min-h-screen flex flex-col">

    <!-- Navigation -->
    <nav class="border-b border-gray-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary rounded flex items-center justify-center text-white font-bold text-lg">
                        E
                    </div>
                    <span class="font-bold text-xl tracking-tight">Eduva</span>
                </div>
                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium bg-primary text-white hover:bg-primary-dark px-4 py-2 rounded-lg transition-colors">Masuk ke Portal</a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="flex-grow flex flex-col items-center justify-center pt-24 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight mb-6 leading-tight">
                Sistem Manajemen Pembelajaran <br/> Terintegrasi
            </h1>
            
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                Platform pendidikan modern yang memadukan manajemen kelas konvensional dengan asisten pembelajaran cerdas. Didesain khusus untuk sekolah di Indonesia.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('login') }}" class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-primary hover:bg-primary-dark transition-colors shadow-sm">
                    Masuk ke Sistem
                </a>
                <a href="#fitur" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Pelajari Fitur
                </a>
            </div>
        </div>

        <!-- Dashboard Mockup Image -->
        <div class="mt-20 w-full max-w-5xl rounded-xl border border-gray-200 shadow-card bg-gray-50 overflow-hidden">
            <div class="w-full h-8 bg-gray-100 border-b border-gray-200 flex items-center px-4 gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-gray-300"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-gray-300"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-gray-300"></div>
            </div>
            <div class="p-8 grid grid-cols-3 gap-6">
                <!-- Sidebar -->
                <div class="col-span-1 border-r border-gray-200 pr-6 hidden md:block">
                    <div class="h-6 w-24 bg-gray-200 rounded mb-6"></div>
                    <div class="space-y-4">
                        <div class="h-8 w-full bg-blue-50 rounded"></div>
                        <div class="h-8 w-full bg-gray-100 rounded"></div>
                        <div class="h-8 w-full bg-gray-100 rounded"></div>
                    </div>
                </div>
                <!-- Main -->
                <div class="col-span-3 md:col-span-2 space-y-6">
                    <div class="h-8 w-1/3 bg-gray-200 rounded"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="h-24 bg-white border border-gray-200 rounded shadow-sm"></div>
                        <div class="h-24 bg-white border border-gray-200 rounded shadow-sm"></div>
                    </div>
                    <div class="h-48 bg-white border border-gray-200 rounded shadow-sm"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Feature Highlights -->
    <section id="fitur" class="py-20 bg-gray-50 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Fitur Lengkap untuk Sekolah</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">Infrastruktur yang kuat untuk mendukung kegiatan belajar mengajar.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-subtle">
                    <div class="w-10 h-10 bg-blue-50 rounded flex items-center justify-center text-primary mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Manajemen Materi</h3>
                    <p class="text-gray-600 text-sm">Unggah dan kelola modul pembelajaran, video, dan dokumen dengan rapi untuk setiap kelas.</p>
                </div>
                <!-- Feature 2 -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-subtle">
                    <div class="w-10 h-10 bg-emerald-50 rounded flex items-center justify-center text-accent mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Monitoring Ujian</h3>
                    <p class="text-gray-600 text-sm">Sistem ujian yang mengawasi aktivitas siswa secara otomatis untuk menjaga integritas penilaian.</p>
                </div>
                <!-- Feature 3 -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-subtle">
                    <div class="w-10 h-10 bg-slate-100 rounded flex items-center justify-center text-secondary mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Tutor Berbasis AI</h3>
                    <p class="text-gray-600 text-sm">Asisten pembelajaran yang mengambil referensi langsung dari materi yang diajarkan oleh guru.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white py-8 border-t border-gray-200 text-center">
        <p class="text-gray-500 text-sm">
            &copy; {{ date('Y') }} Eduva. Hak cipta dilindungi.
        </p>
    </footer>

</body>
</html>

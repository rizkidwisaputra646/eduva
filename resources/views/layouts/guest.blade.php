<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Eduva') }} - Autentikasi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50 flex min-h-screen">
    
    <!-- Left: Branding / Visual (Hidden on mobile) -->
    <div class="hidden lg:flex lg:flex-1 flex-col justify-center items-center bg-gradient-to-br from-primary to-accent relative overflow-hidden">
        <div class="absolute inset-0 bg-black/10 backdrop-blur-sm"></div>
        <div class="z-10 text-center px-12 text-white">
            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-bold text-3xl mx-auto mb-6 shadow-xl border border-white/30">
                E
            </div>
            <h1 class="text-4xl font-bold mb-4 tracking-tight">Selamat Datang di Eduva</h1>
            <p class="text-lg text-indigo-100 max-w-md mx-auto leading-relaxed">Platform pembelajaran era baru dengan bantuan AI Tutor yang membuat proses belajarmu jauh lebih efektif.</p>
        </div>
        
        <!-- Decorative blobs -->
        <div class="absolute top-1/4 -left-10 w-72 h-72 bg-white opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 -right-10 w-96 h-96 bg-secondary opacity-20 rounded-full blur-3xl"></div>
    </div>

    <!-- Right: Auth Form -->
    <div class="flex-1 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-md">
            
            <div class="text-center lg:hidden mb-8">
                <a href="/" class="inline-flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white font-bold text-xl shadow-md">E</div>
                    <span class="font-bold text-2xl text-gray-900 tracking-tight">Eduva</span>
                </a>
            </div>

            <div class="bg-white p-8 shadow-sm sm:shadow-lg border border-gray-100 sm:rounded-3xl w-full">
                {{ $slot }}
            </div>

            <p class="mt-8 text-center text-xs text-gray-500">
                &copy; {{ date('Y') }} Eduva. Seluruh hak cipta dilindungi.
            </p>
        </div>
    </div>

</body>
</html>

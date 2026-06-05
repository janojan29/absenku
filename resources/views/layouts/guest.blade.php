<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#070d1a">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Login — Sistem Absensi Digital Siswa">

        <title>{{ config('app.name', 'Absensi Digital') }} Login</title>

        <!-- Fonts -->
        <link href="{{ asset('vendor/fonts/app-fonts.css') }}" rel="stylesheet">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
        <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('icon-192.png') }}">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">
            {{-- Left Panel — Hero (hidden on mobile) --}}
            <div class="hidden lg:flex lg:w-1/2 xl:w-[55%] relative overflow-hidden items-center justify-center bg-cover bg-center bg-no-repeat"
                 style="background-image: url('{{ asset('images/background.webp') }}'); background-repeat: no-repeat; background-size: cover; background-position: center;">

                {{-- Dark Overlay to make the text readable --}}
                <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(13, 27, 42, 0.85) 0%, rgba(7, 13, 26, 0.90) 100%);"></div>

                {{-- Decorative circles --}}
                <div class="absolute -top-20 -left-20 w-80 h-80 rounded-full opacity-[0.07]" style="background: radial-gradient(circle, #60a5f5, transparent);"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full opacity-[0.05]" style="background: radial-gradient(circle, #3b82d4, transparent);"></div>
                <div class="absolute top-1/4 right-10 w-40 h-40 rounded-full opacity-[0.08] animate-float" style="background: radial-gradient(circle, #60a5f5, transparent); animation-delay: 0.5s;"></div>
                <div class="absolute bottom-1/4 left-16 w-24 h-24 rounded-2xl rotate-45 opacity-[0.06] animate-float" style="background: #3b82d4; animation-delay: 1s;"></div>
                <div class="absolute top-1/3 left-1/4 w-16 h-16 rounded-xl rotate-12 opacity-[0.05] animate-float" style="background: #60a5f5; animation-delay: 1.5s;"></div>

                {{-- Content --}}
                <div class="relative z-10 max-w-lg px-12 text-center">
                    {{-- Illustration SVG --}}
                    <div class="mb-10 flex justify-center">
                        <img src="{{ asset('images/logo_transparent.webp') }}" class="w-36 h-36 object-contain" alt="SMKN Bungursari Logo">
                    </div>

                    <h1 class="text-4xl xl:text-5xl font-bold text-white mb-4 leading-tight animate-fade-slide-up">
                        ABSENKU<br>
                        <span class="text-navy-300">Absensi Digital</span>
                    </h1>
                    <p class="text-navy-300/80 text-lg leading-relaxed animate-fade-slide-up stagger-1">
                        SMK NEGERI BUNGURSARI 
                    </p>
                </div>
            </div>

            {{-- Right Panel — Login Form --}}
            <div class="flex-1 flex items-center justify-center relative" style="background: #ffffff;">
                @unless($hideAuthHeader ?? false)
                    {{-- Mobile gradient header --}}
                    <div class="lg:hidden absolute top-0 left-0 right-0 h-40" style="background: linear-gradient(135deg, #0d1b2a 0%, #1e4d8c 100%); border-radius: 0 0 40px 40px;">
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-white flex items-center justify-center overflow-hidden">
                                    <img src="{{ asset('images/logo.png') }}" class="w-10 h-10 object-contain" alt="SMKN Bungursari Logo">
                                </div>
                                <h1 class="text-white font-bold text-lg">Absensi Digital</h1>
                            </div>
                        </div>
                    </div>
                @endunless

                {{-- Main Content / Form Container --}}
                <div class="w-full max-w-md p-6 sm:p-12 relative z-10 mt-32 lg:mt-0 bg-white lg:bg-transparent rounded-3xl lg:rounded-none shadow-xl lg:shadow-none mx-4 sm:mx-0">
                    @unless($hideAuthHeader ?? false)
                        {{-- Desktop Header --}}
                        <div class="hidden lg:block text-center mb-10">
                            <h2 class="text-3xl lg:text-4xl font-bold text-navy-900">Selamat Datang</h2>
                            <p class="text-gray-600 text-base lg:text-lg mt-2">Silahkan Masuk ke akun Anda</p>
                        </div>

                        {{-- Mobile heading (below gradient) --}}
                        <div class="lg:hidden text-center mb-8">
                            <h2 class="text-2xl font-bold text-navy-900">Masuk ke Akun</h2>
                            <p class="text-bw-400 text-sm mt-1">Gunakan NISN, NIP, atau email Anda</p>
                        </div>
                    @endunless

                    {{ $slot }}
                </div>
            </div>
        </div>

        @include('layouts.partials.toast')
    </body>
</html>

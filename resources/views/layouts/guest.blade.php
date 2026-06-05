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

        <title>{{ config('app.name', 'Absensi Digital') }} — Login</title>

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
            <div class="hidden lg:flex lg:w-1/2 xl:w-[55%] relative overflow-hidden items-center justify-center"
                 style="background: linear-gradient(135deg, #0d1b2a 0%, #1e4d8c 50%, #2563b8 100%);">

                {{-- Decorative circles --}}
                <div class="absolute -top-20 -left-20 w-80 h-80 rounded-full opacity-[0.07]" style="background: radial-gradient(circle, #60a5f5, transparent);"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full opacity-[0.05]" style="background: radial-gradient(circle, #3b82d4, transparent);"></div>
                <div class="absolute top-1/4 right-10 w-40 h-40 rounded-full opacity-[0.08] animate-float" style="background: radial-gradient(circle, #60a5f5, transparent); animation-delay: 0.5s;"></div>
                <div class="absolute bottom-1/4 left-16 w-24 h-24 rounded-2xl rotate-45 opacity-[0.06] animate-float" style="background: #3b82d4; animation-delay: 1s;"></div>
                <div class="absolute top-1/3 left-1/4 w-16 h-16 rounded-xl rotate-12 opacity-[0.05] animate-float" style="background: #60a5f5; animation-delay: 1.5s;"></div>

                {{-- Content --}}
                <div class="relative z-10 max-w-lg px-12 text-center">
                    {{-- Illustration SVG --}}
                    <div class="mb-10 animate-float">
                        <svg class="w-48 h-48 mx-auto" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            {{-- Book / Attendance --}}
                            <rect x="40" y="50" width="120" height="110" rx="12" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/>
                            <rect x="50" y="60" width="100" height="16" rx="4" fill="rgba(96,165,245,0.3)"/>
                            <rect x="50" y="84" width="70" height="8" rx="3" fill="rgba(255,255,255,0.15)"/>
                            <rect x="50" y="100" width="85" height="8" rx="3" fill="rgba(255,255,255,0.12)"/>
                            <rect x="50" y="116" width="60" height="8" rx="3" fill="rgba(255,255,255,0.09)"/>
                            {{-- Checkmarks --}}
                            <circle cx="140" cy="88" r="8" fill="rgba(16,185,129,0.3)" stroke="rgba(16,185,129,0.5)" stroke-width="1"/>
                            <path d="M136 88L139 91L144 85" stroke="rgba(16,185,129,0.8)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="140" cy="104" r="8" fill="rgba(16,185,129,0.3)" stroke="rgba(16,185,129,0.5)" stroke-width="1"/>
                            <path d="M136 104L139 107L144 101" stroke="rgba(16,185,129,0.8)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            {{-- Location pin --}}
                            <circle cx="160" cy="40" r="18" fill="rgba(37,99,184,0.3)" stroke="rgba(96,165,245,0.4)" stroke-width="1.5"/>
                            <path d="M160 33C157.2 33 155 35.2 155 38C155 42.5 160 47 160 47C160 47 165 42.5 165 38C165 35.2 162.8 33 160 33Z" fill="rgba(96,165,245,0.6)"/>
                            <circle cx="160" cy="38" r="2" fill="rgba(255,255,255,0.7)"/>
                        </svg>
                    </div>

                    <h1 class="text-4xl xl:text-5xl font-bold text-white mb-4 leading-tight animate-fade-slide-up">
                        Absensi Digital<br>
                        <span class="text-navy-300">Praktis & Modern</span>
                    </h1>
                    <p class="text-navy-300/80 text-lg leading-relaxed animate-fade-slide-up stagger-1">
                        Pantau kehadiran siswa secara real-time dengan teknologi GPS dan dashboard interaktif.
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
                                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-white/10 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                                    </svg>
                                </div>
                                <h1 class="text-white font-bold text-lg">Absensi Digital</h1>
                            </div>
                        </div>
                    </div>
                @endunless

                {{-- Main Content / Form Container --}}
                <div class="w-full max-w-md p-6 sm:p-12 relative z-10 mt-32 lg:mt-0 bg-white lg:bg-transparent rounded-3xl lg:rounded-none shadow-xl lg:shadow-none mx-4 sm:mx-0">
                    @unless($hideAuthHeader ?? false)
                        {{-- Desktop Logo --}}
                        <div class="hidden lg:block text-center mb-10">
                            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-navy-500 to-navy-600 flex items-center justify-center shadow-glow">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-navy-900">Selamat Datang</h2>
                            <p class="text-bw-400 text-sm mt-1">Masuk ke akun Anda</p>
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

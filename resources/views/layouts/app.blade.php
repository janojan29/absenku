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
        <meta name="description" content="Sistem Absensi Digital Siswa — SMA Digital Nusantara">

        <title>{{ config('app.name', 'Absensi Digital') }} — {{ $title ?? 'Dashboard' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
        <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('icon-192.png') }}">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-space-900 text-surface-50">
        {{-- Glow decoration at top --}}
        <div class="fixed top-0 left-0 w-full h-64 pointer-events-none z-0" style="background: radial-gradient(ellipse at top, rgba(37,99,184,0.06) 0%, transparent 70%);"></div>

        <div class="relative min-h-screen flex">
            {{-- Sidebar --}}
            <div class="hidden lg:block">
                @include('layouts.partials.sidebar')
            </div>

            {{-- Main Content Wrapper --}}
            <div class="flex-1 flex flex-col min-h-screen min-w-0 mobile-shell">

                {{-- Topbar --}}
                @include('layouts.partials.topbar')

                {{-- Page Content --}}
                <main class="flex-1 mobile-main-content">
                    {{-- Page Header --}}
                    @if (isset($header))
                        <div class="mb-6 animate-fade-slide-up">
                            {{ $header }}
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="page-transition-enter">
                        {{ $slot }}
                    </div>
                </main>

                {{-- Footer --}}
                <footer class="hidden lg:block px-4 sm:px-6 lg:px-8 py-4 text-center text-xs text-bw-400">
                    &copy; {{ date('Y') }} Absensi Digital — {{ \App\Models\SchoolSetting::singleton()->name }}
                </footer>
            </div>
        </div>

        {{-- Mobile Bottom Navigation --}}
        <x-mobile-bottom-nav :role="auth()->user()?->getRoleNames()->first()" />

        {{-- Toast Container --}}
        @include('layouts.partials.toast')
        
        {{-- Confirm Modal Container --}}
        @include('layouts.partials.confirm-modal')

        @livewireScripts
    </body>
</html>

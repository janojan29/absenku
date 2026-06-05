{{-- Topbar partial — Glassmorphic sticky header --}}
<header
    class="fixed lg:sticky top-0 left-0 right-0 lg:left-auto lg:right-auto z-20 h-topbar lg:h-topbar flex items-center justify-between px-4 lg:px-6 border-b border-bw-300/70 mobile-top-header"
    style="background: rgba(7,13,26,0.90); border-bottom-color: rgba(255,255,255,0.06); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); box-shadow: 0 1px 0 rgba(59,130,246,0.1);"
    x-data="clock()"
>
    @php
        $schoolName = \App\Models\SchoolSetting::singleton()->name;
    @endphp

    {{-- Left Section --}}
    <div class="flex items-center gap-3">
        <div class="lg:hidden flex items-center gap-2">
            <div class="text-[16px] font-semibold text-white tracking-tight truncate max-w-[58vw]">{{ $schoolName }}</div>
        </div>

        {{-- Breadcrumb --}}
        <nav class="hidden sm:flex items-center gap-2 text-sm">
            <span class="text-white/30"></span>
        </nav>
    </div>

    {{-- Right Section --}}
    <div class="flex items-center gap-2 sm:gap-4">
        <div class="lg:hidden relative" x-data="{ mobileUserMenu: false }">
            <button
                type="button"
                @click="mobileUserMenu = !mobileUserMenu"
                @click.outside="mobileUserMenu = false"
                class="touch-target w-9 h-9 rounded-full bg-white/10 border border-white/15 flex items-center justify-center text-xs font-semibold text-white"
                aria-label="Buka menu akun"
            >
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </button>

            <div
                x-show="mobileUserMenu"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute right-0 mt-2 w-44 rounded-xl overflow-hidden"
                style="background: rgba(10, 17, 32, 0.98); border: 1px solid rgba(255,255,255,0.10); box-shadow: 0 12px 30px rgba(0,0,0,0.35);"
            >
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2.5 text-sm text-white/80 hover:bg-white/5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    Profil
                </a>

                <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2.5 text-sm text-red-300 hover:bg-white/5 text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>

        {{-- Date/Time Display --}}
        <div class="hidden md:flex items-center gap-2 text-sm text-white/65">
            <svg class="w-4 h-4 text-electric-300/80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span x-text="date" class="text-white/50 text-xs"></span>
            <span class="text-electric-300 font-semibold tabular-nums" x-text="time"></span>
        </div>
    </div>
</header>


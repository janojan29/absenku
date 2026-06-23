{{-- Sidebar partial — Dark navy gradient, always visible on desktop --}}
<aside
    class="sticky top-0 h-screen z-40 flex flex-col overflow-hidden shrink-0"
    style="width: 260px; background: linear-gradient(145deg, #1a2744 0%, #1e3a5f 100%);"
>
    {{-- Logo Area --}}
    <div class="h-18 flex items-center gap-3 px-5 border-b border-white/10 shrink-0">
        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shrink-0 overflow-hidden">
            <img src="{{ asset('images/logo.webp') }}" class="w-8 h-8 object-contain" alt="SMKN Bungursari Logo">
        </div>
        <div class="min-w-0 overflow-hidden">
            <div class="text-white font-bold text-sm leading-tight truncate">AbsensKu</div>
            <div class="text-navy-300 text-[11px] truncate">{{ \App\Models\SchoolSetting::singleton()->name }}</div>
        </div>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6 scrollbar-thin">

        {{-- MENU SISWA --}}
        @role('siswa')
        <div>
            <div class="px-2 mb-2 text-[10px] font-semibold tracking-widest uppercase text-navy-300/60">
                Menu
            </div>

            {{-- Absensi --}}
            <a href="{{ route('attendance.index') }}"
               class="sidebar-link group {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <span class="sidebar-text">Absensi</span>
            </a>

        </div>
        @endrole

        {{-- MENU GURU / GURU WALIKELAS / PETUGAS PIKET --}}
        @if(auth()->user()->hasAnyRole(['guru', 'guru_walikelas', 'petugas_piket']) && !auth()->user()->hasRole('admin'))
        <div>
            <div class="px-2 mb-2 text-[10px] font-semibold tracking-widest uppercase text-navy-300/60">
                Akademik
            </div>

            {{-- Dashboard --}}
            <a href="{{ route('teacher.dashboard') }}"
               class="sidebar-link group {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
                    </svg>
                </div>
                <span class="sidebar-text">Dashboard</span>
            </a>

            {{-- Rekap Absensi --}}
            @if(auth()->user()->hasAnyRole(['guru_walikelas', 'petugas_piket']))
            <a href="{{ route('teacher.report') }}"
               class="sidebar-link group {{ request()->routeIs('teacher.report*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <span class="sidebar-text">Rekap Absensi</span>
            </a>
            @endif

            {{-- Persetujuan Izin (Petugas Piket) --}}
            @role('petugas_piket')
            @php
                $pendingLeaveCount = \App\Models\LeaveRequest::query()
                    ->where('status', 'pending')
                    ->count();
            @endphp
                <a href="{{ route('picket.leave-requests.index') }}"
                    class="sidebar-link group relative {{ request()->routeIs('picket.leave-requests.*') ? 'active' : '' }}">
                <div class="sidebar-icon relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
                <span class="sidebar-text">Persetujuan Izin</span>
                @if ($pendingLeaveCount > 0)
                    <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-semibold" style="background:#ef4444;color:#fff;border:1px solid #ef4444;box-shadow:0 4px 12px rgba(239,68,68,0.35);">
                        {{ $pendingLeaveCount > 99 ? '99+' : $pendingLeaveCount }}
                    </span>
                @endif
            </a>
            @endrole
        </div>
        @endif

        {{-- MENU ADMIN --}}
        @role('admin')
        <div>
            <div class="px-2 mb-2 text-[10px] font-semibold tracking-widest uppercase text-navy-300/60">
                Administrasi
            </div>

            <a href="{{ route('admin.settings.edit') }}"
               class="sidebar-link group {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <span class="sidebar-text">Pengaturan</span>
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="sidebar-link group {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <span class="sidebar-text">Pengguna</span>
            </a>

            <a href="{{ route('admin.class-rooms.index') }}"
               class="sidebar-link group {{ request()->routeIs('admin.class-rooms.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                    </svg>
                </div>
                <span class="sidebar-text">Kelas</span>
            </a>

            <a href="{{ route('admin.students.index') }}"
               class="sidebar-link group {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                    </svg>
                </div>
                <span class="sidebar-text">Siswa</span>
            </a>

            <a href="{{ route('admin.teachers.index') }}"
               class="sidebar-link group {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <span class="sidebar-text">Guru</span>
            </a>
        </div>
        @endrole
    </nav>

    {{-- User Profile Card with Dropdown --}}
    <div class="shrink-0 border-t border-white/10 p-3" x-data="{ userMenu: false }">
        {{-- Trigger --}}
        <button
            @click="userMenu = !userMenu"
            @click.outside="userMenu = false"
            class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-white/8 transition-all duration-250 group cursor-pointer text-left"
        >
            {{-- Avatar --}}
            <div class="w-10 h-10 rounded-full bg-navy-500/40 flex items-center justify-center shrink-0 ring-2 ring-navy-400/20 group-hover:ring-navy-400/40 transition-all duration-250">
                <span class="text-white font-semibold text-sm">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
            </div>
            <div class="min-w-0 flex-1 overflow-hidden">
                <div class="text-white text-sm font-medium truncate">{{ Auth::user()->name }}</div>
                <div class="text-navy-300/70 text-[11px] truncate">
                    @php
                        $roles = Auth::user()->getRoleNames()->toArray();
                        $roleLabels = [
                            'siswa' => 'Siswa',
                            'guru' => 'Guru',
                            'guru_walikelas' => 'Wali Kelas',
                            'petugas_piket' => 'Petugas Piket',
                            'admin' => 'Administrator',
                        ];
                        $displayRole = collect($roles)->map(fn($r) => $roleLabels[$r] ?? $r)->implode(', ');
                    @endphp
                    {{ $displayRole }}
                </div>
            </div>
            {{-- Chevron --}}
            <svg class="w-4 h-4 text-navy-300/50 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': userMenu }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
            </svg>
        </button>

        {{-- Dropdown (opens upward) --}}
        <div
            x-show="userMenu"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="mb-2 rounded-xl overflow-hidden"
            style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);"
        >
            {{-- Email --}}
            <div class="px-4 py-2.5 border-b border-white/5">
                <div class="text-[11px] text-navy-300/60 truncate">{{ Auth::user()->email }}</div>
            </div>

            {{-- Profile Link --}}
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/5 transition-colors duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                Profil
            </a>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}" class="border-t border-white/5">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-white/5 transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Keluar
                </button>
            </form>
        </div>
    </div>


</aside>

<style>
    /* Sidebar link styling */
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        color: rgba(255, 255, 255, 0.65);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.06);
        color: rgba(255, 255, 255, 0.9);
    }

    .sidebar-link.active {
        background: rgba(37, 99, 184, 0.25);
        color: #60a5f5;
        border-left: 3px solid #3b82d4;
        padding-left: 9px;
    }

    .sidebar-link.active .sidebar-icon {
        color: #3b82d4;
        opacity: 1;
    }

    .sidebar-icon {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.7;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        shrink: 0;
        flex-shrink: 0;
    }

    .sidebar-link:hover .sidebar-icon,
    .sidebar-link.active .sidebar-icon {
        opacity: 1;
    }

    .sidebar-text {
        white-space: nowrap;
        overflow: hidden;
    }
</style>

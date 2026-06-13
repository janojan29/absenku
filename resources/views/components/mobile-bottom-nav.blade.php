@props([
    'role' => null,
    'active' => null,
])

@php
    $user = auth()->user();
    $resolvedRole = $role;
    $pendingLeaveCount = 0;

    if (!$resolvedRole && $user) {
        if ($user->hasRole('admin')) {
            $resolvedRole = 'admin';
        } elseif ($user->hasRole('petugas_piket')) {
            $resolvedRole = 'petugas_piket';
        } elseif ($user->hasRole('guru_walikelas')) {
            $resolvedRole = 'guru_walikelas';
        } elseif ($user->hasRole('guru')) {
            $resolvedRole = 'guru';
        } elseif ($user->hasRole('siswa')) {
            $resolvedRole = 'siswa';
        }
    }

    $isPicket = in_array($resolvedRole, ['petugas_piket'], true);
    $isTeacher = in_array($resolvedRole, ['guru', 'guru_walikelas'], true);

    if ($isPicket) {
        $pendingLeaveCount = \App\Models\LeaveRequest::query()
            ->where('status', 'pending')
            ->count();
    }

    $items = [];

    if ($resolvedRole === 'siswa') {
        $items = [];
    } elseif ($isPicket) {
        $items = [
            ['label' => 'Beranda', 'href' => route('teacher.dashboard'), 'icon' => 'home', 'active' => request()->routeIs('teacher.dashboard')],
            ['label' => 'Persetujuan', 'href' => route('picket.leave-requests.index'), 'icon' => 'check-circle', 'active' => request()->routeIs('picket.leave-requests.*'), 'badge' => $pendingLeaveCount],
            ['label' => 'Rekap', 'href' => route('teacher.report'), 'icon' => 'bar-chart', 'active' => request()->routeIs('teacher.report*')],
            ['label' => 'Profil', 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
        ];
    } elseif ($isTeacher) {
        if ($resolvedRole === 'guru_walikelas') {
            $items = [
                ['label' => 'Beranda', 'href' => route('teacher.dashboard'), 'icon' => 'home', 'active' => request()->routeIs('teacher.dashboard')],
                ['label' => 'Rekap', 'href' => route('teacher.report'), 'icon' => 'bar-chart', 'active' => request()->routeIs('teacher.report*')],
                ['label' => 'Profil', 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
            ];
        } else {
            $items = [
                ['label' => 'Beranda', 'href' => route('teacher.dashboard'), 'icon' => 'home', 'active' => request()->routeIs('teacher.dashboard')],
                ['label' => 'Profil', 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
            ];
        }
    } elseif ($resolvedRole === 'admin') {
        $items = [
            ['label' => 'Beranda', 'href' => route('admin.users.index'), 'icon' => 'home', 'active' => request()->routeIs('admin.users.index')],
            ['label' => 'Siswa', 'href' => route('admin.students.index'), 'icon' => 'user', 'active' => request()->routeIs('admin.students.*')],
            ['label' => 'Kelas', 'href' => route('admin.class-rooms.index'), 'icon' => 'school', 'active' => request()->routeIs('admin.class-rooms.*')],
            ['label' => 'Setting', 'href' => route('admin.settings.edit'), 'icon' => 'settings', 'active' => request()->routeIs('admin.settings.*')],
            ['label' => 'Guru', 'href' => route('admin.teachers.index'), 'icon' => 'users', 'active' => request()->routeIs('admin.teachers.*')],
        ];
    }

    $renderIcon = function (string $name) {
        return match ($name) {
            'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />',
            'hand' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75V6.75a1.5 1.5 0 1 1 3 0v3m0-3V5.25a1.5 1.5 0 1 1 3 0v4.5m0 0V6.75a1.5 1.5 0 1 1 3 0v6.75a6 6 0 0 1-6 6H9a6 6 0 0 1-6-6V10.5a1.5 1.5 0 1 1 3 0v3" />',
            'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 3h3m-1.5-12a2.25 2.25 0 0 1 2.121 1.5h2.629a2.25 2.25 0 0 1 2.25 2.25v11.25a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5h2.629A2.25 2.25 0 0 1 10.5 3Z" />',
            'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75" />',
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a8.966 8.966 0 0 1-6 2.03 8.966 8.966 0 0 1-6-2.03m12 0a6.75 6.75 0 1 0-12 0m9-8.97a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
            'check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            'bar-chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />',
            'settings' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3m-7.5 6h15m-12 6h9" />',
            'school' => '<path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-6 9 6m-18 0v9.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V9m-9 12V12" />',
            'user' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />',
            default => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />',
        };
    };
@endphp

@if (!empty($items))
    <nav class="mobile-bottom-nav lg:hidden" aria-label="Navigasi mobile utama">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" class="mobile-bottom-nav-item {{ $item['active'] ? 'is-active' : '' }}">
                <span class="mobile-bottom-nav-icon-wrap">
                    @if ($item['active'])
                        <span class="mobile-bottom-nav-dot" aria-hidden="true"></span>
                    @endif
                    @if (!empty($item['badge']))
                        <span class="mobile-bottom-nav-badge" aria-label="{{ $item['badge'] }} pengajuan ijin menunggu persetujuan">
                            {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                        </span>
                    @endif
                    <svg class="mobile-bottom-nav-icon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        {!! $renderIcon($item['icon']) !!}
                    </svg>
                </span>
                <span class="mobile-bottom-nav-label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endif

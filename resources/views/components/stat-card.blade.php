{{--
    Stat Card Component
    
    Usage:
    <x-stat-card
        label="Hadir"
        :value="$count"
        type="success"      // success | warning | danger | info | purple
        icon="check"        // check | clock | x-mark | document | chart
        :trend="5.2"        // optional: percentage trend
        trend-up            // optional: trend direction
        :total="$total"     // optional: progress bar denominator
    />
--}}

@props([
    'label' => 'Label',
    'value' => 0,
    'type' => 'success',
    'icon' => 'check',
    'trend' => null,
    'trendUp' => true,
    'total' => null,
])

@php
    $gradients = [
        'success' => 'from-emerald-500 to-emerald-600',
        'warning' => 'from-amber-400 to-amber-500',
        'danger'  => 'from-red-500 to-red-600',
        'info'    => 'from-cyan-500 to-cyan-600',
        'purple'  => 'from-violet-500 to-violet-600',
        'navy'    => 'from-navy-500 to-navy-600',
    ];
    $gradient = $gradients[$type] ?? $gradients['navy'];
    
    $trendColors = [
        'success' => 'text-emerald-600',
        'warning' => 'text-amber-600',
        'danger'  => 'text-red-600',
        'info'    => 'text-cyan-600',
    ];
    $trendColor = $trendUp ? 'text-emerald-600' : 'text-red-600';
    
    $progressPercent = $total ? min(100, round(($value / max($total, 1)) * 100)) : null;
@endphp

<div {{ $attributes->merge(['class' => 'card card-hover']) }}>
    {{-- Top Row: Icon + Trend --}}
    <div class="flex items-start justify-between mb-4">
        {{-- Icon Container --}}
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $gradient }} flex items-center justify-center shadow-sm">
            @switch($icon)
                @case('check')
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    @break
                @case('clock')
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    @break
                @case('x-mark')
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    @break
                @case('document')
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    @break
                @case('chart')
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    @break
                @default
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
            @endswitch
        </div>

        {{-- Trend Badge --}}
        @if($trend !== null)
            <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold {{ $trendColor }} bg-current/5">
                @if($trendUp)
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                    </svg>
                @else
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" />
                    </svg>
                @endif
                {{ number_format(abs($trend), 1) }}%
            </div>
        @endif
    </div>

    {{-- Value --}}
    <div class="text-display-md text-navy-900 tabular-nums mb-1">
        {{ (int) $value }}
    </div>

    {{-- Label --}}
    <div class="text-sm text-bw-400 font-medium">
        {{ $label }}
    </div>

    {{-- Progress Bar --}}
    @if($progressPercent !== null)
        <div class="mt-3 h-1.5 bg-bw-200 rounded-full overflow-hidden">
            <div
                class="h-full rounded-full bg-gradient-to-r {{ $gradient }} progress-bar-fill"
                style="width: {{ $progressPercent }}%"
            ></div>
        </div>
    @endif
</div>

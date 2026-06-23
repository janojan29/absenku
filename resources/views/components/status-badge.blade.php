{{--
    Status Badge Component
    
    Usage:
    <x-status-badge status="present" />
    <x-status-badge status="late" label="Terlambat 10 menit" />
--}}

@props([
    'status' => 'unknown',
    'label' => null,
])

@php
    $configs = [
        'present' => ['class' => 'badge-hadir', 'label' => 'Hadir'],
        'hadir'   => ['class' => 'badge-hadir', 'label' => 'Hadir'],
        'late'    => ['class' => 'badge-terlambat', 'label' => 'Terlambat'],
        'terlambat' => ['class' => 'badge-terlambat', 'label' => 'Terlambat'],
        'absent'  => ['class' => 'badge-alfa', 'label' => 'Alfa'],
        'alfa'    => ['class' => 'badge-alfa', 'label' => 'Alfa'],
        'leave'   => ['class' => 'badge-izin', 'label' => 'Izin'],
        'izin'    => ['class' => 'badge-izin', 'label' => 'Izin'],
        'ijin'    => ['class' => 'badge-izin', 'label' => 'Izin'],
        'sick'    => ['class' => 'badge-sakit', 'label' => 'Sakit'],
        'sakit'   => ['class' => 'badge-sakit', 'label' => 'Sakit'],
        'approved'  => ['class' => 'badge-hadir', 'label' => 'Disetujui'],
        'rejected'  => ['class' => 'badge-alfa', 'label' => 'Ditolak'],
        'pending'   => ['class' => 'badge-terlambat', 'label' => 'Menunggu'],
        'unknown' => ['class' => 'badge-belum', 'label' => 'Belum Absen'],
        'holiday' => ['class' => 'badge-belum', 'label' => 'Libur'],
        'libur'   => ['class' => 'badge-belum', 'label' => 'Libur'],
    ];
    $config = $configs[$status] ?? $configs['unknown'];
    $displayLabel = $label ?? $config['label'];
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $config['class']]) }}>
    <span class="badge-dot"></span>
    {{ $displayLabel }}
</span>

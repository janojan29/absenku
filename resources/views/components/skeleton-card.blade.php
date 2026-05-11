{{--
    Skeleton Card Component
    
    Usage:
    <x-skeleton-card />
    <x-skeleton-card class="h-40" />
--}}

@props([])

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="flex items-start justify-between mb-4">
        <div class="skeleton skeleton-circle w-12 h-12"></div>
        <div class="skeleton w-16 h-6 rounded-full"></div>
    </div>
    <div class="skeleton skeleton-text w-24 h-8 mb-2"></div>
    <div class="skeleton skeleton-text short"></div>
    <div class="skeleton w-full h-1.5 mt-3 rounded-full"></div>
</div>

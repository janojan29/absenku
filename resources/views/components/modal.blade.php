{{--
    Modal Component — Redesigned with backdrop blur and fadeScaleIn
    
    Usage:
    <x-modal name="confirm-delete" :show="false" maxWidth="md">
        <div class="p-8">
            ...content...
        </div>
    </x-modal>
--}}

@props([
    'name',
    'show' => false,
    'maxWidth' => 'md',
    'focusable' => true,
])

@php
    $maxWidthClasses = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)].filter(el => !el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            {{ $focusable ? '$nextTick(() => firstFocusable()?.focus())' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable()?.focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable()?.focus()"
    x-show="show"
    class="fixed inset-0 overflow-y-auto z-50"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0"
        style="background: rgba(10, 15, 30, 0.7); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"
        @click="show = false"
    ></div>

    {{-- Modal Content --}}
    <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full {{ $maxWidthClasses }} bg-bw-50 rounded-2xl shadow-xl overflow-hidden"
        >
            {{ $slot }}
        </div>
    </div>
</div>

@php
    $statusMessage = session('status');
    $translations = [
        'profile-updated' => 'Profil berhasil diperbarui.',
        'password-updated' => 'Kata sandi berhasil diperbarui.',
        'verification-link-sent' => 'Tautan verifikasi baru telah dikirim ke alamat email Anda.',
    ];
    if (is_string($statusMessage) && isset($translations[$statusMessage])) {
        $statusMessage = $translations[$statusMessage];
    }
@endphp

{{-- Centered Popup Notification System --}}
<div
    x-data="toastManager()"
    x-init="
        $nextTick(() => {
            @if ($statusMessage)
                add('{{ $statusMessage }}', 'success', 2000);
            @endif
            @if (session('error'))
                add('{{ session('error') }}', 'error', 3000);
            @endif
            @if ($errors->any())
                add('{{ $errors->first() }}', 'error', 3000);
            @endif
        });
    "
    x-on:toast.window="add($event.detail.message, $event.detail.type || 'success', 2000)"
    class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none"
    id="toast-container"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            :class="{
                'animate-fade-scale-in': toast.visible,
                'animate-fade-out': !toast.visible,
            }"
            class="pointer-events-auto absolute"
        >
            <div
                :class="{
                    'border-emerald-200 bg-white': toast.type === 'success',
                    'border-red-200 bg-white': toast.type === 'error',
                    'border-amber-200 bg-white': toast.type === 'warning',
                    'border-cyan-200 bg-white': toast.type === 'info',
                }"
                class="flex items-center gap-4 px-8 py-5 rounded-2xl shadow-xl border min-w-[280px] max-w-md"
                style="box-shadow: 0 20px 60px rgba(13,27,42,0.25), 0 4px 16px rgba(13,27,42,0.1);"
            >
                {{-- Icon --}}
                <div class="shrink-0">
                    <template x-if="toast.type === 'success'">
                        <div class="w-11 h-11 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <div class="w-11 h-11 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <div class="w-11 h-11 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <div class="w-11 h-11 rounded-full bg-cyan-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                        </div>
                    </template>
                </div>

                {{-- Message --}}
                <div class="flex-1 min-w-0">
                    <div
                        :class="{
                            'text-emerald-800': toast.type === 'success',
                            'text-red-800': toast.type === 'error',
                            'text-amber-800': toast.type === 'warning',
                            'text-cyan-800': toast.type === 'info',
                        }"
                        class="text-sm font-semibold"
                        x-text="toast.type === 'success' ? 'Berhasil!' : (toast.type === 'error' ? 'Gagal!' : (toast.type === 'warning' ? 'Perhatian!' : 'Info'))"
                    ></div>
                    <p class="text-sm text-gray-600 mt-0.5" x-text="toast.message"></p>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
    .animate-fade-scale-in {
        animation: fadeScaleIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    .animate-fade-out {
        animation: fadeOut 0.2s cubic-bezier(0.4, 0, 0.2, 1) both;
    }
</style>

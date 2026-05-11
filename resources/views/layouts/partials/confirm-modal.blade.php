{{-- Global Confirm Modal --}}
<div x-data="{ 
    open: false, 
    title: 'Konfirmasi', 
    message: 'Apakah Anda yakin?', 
    confirmText: 'Ya, Lanjutkan', 
    cancelText: 'Batal',
    type: 'danger', 
    formEl: null 
}" 
@open-confirm.window="
    open = true; 
    title = $event.detail.title || 'Konfirmasi';
    message = $event.detail.message || 'Apakah Anda yakin?';
    confirmText = $event.detail.confirmText || 'Ya, Lanjutkan';
    cancelText = $event.detail.cancelText || 'Batal';
    type = $event.detail.type || 'danger';
    formEl = $event.detail.formEl;
">
    <div
        x-show="open"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-navy-950/60 backdrop-blur-sm"
            @click="open = false"
        ></div>
        
        {{-- Modal Content --}}
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-250 cubic-bezier(0.34, 1.56, 0.64, 1)"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="bg-white rounded-2xl shadow-card w-full max-w-sm overflow-hidden flex flex-col relative z-10"
        >
            <div class="p-6 text-center">
                {{-- Icon --}}
                <div class="w-16 h-16 mx-auto rounded-full mb-4 flex items-center justify-center"
                     :class="type === 'danger' ? 'bg-red-100 text-red-500' : 'bg-amber-100 text-amber-500'">
                    <svg x-show="type === 'danger'" class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg x-show="type === 'warning'" class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                
                <h3 class="font-bold text-lg text-navy-900 mb-2" x-text="title"></h3>
                <p class="text-bw-400 text-sm mb-6" x-text="message"></p>
                
                <div class="flex gap-3 w-full">
                    <button type="button" @click="open = false" class="btn-secondary flex-1 h-11" x-text="cancelText"></button>
                    <button type="button" 
                            @click="if(formEl) formEl.submit(); open = false;" 
                            class="flex-1 h-11 text-white font-semibold rounded-xl transition-all duration-200"
                            :class="type === 'danger' ? 'bg-red-500 hover:bg-red-600 shadow-[0_4px_12px_rgba(239,68,68,0.25)] hover:shadow-[0_6px_16px_rgba(239,68,68,0.4)]' : 'bg-amber-500 hover:bg-amber-600 shadow-[0_4px_12px_rgba(245,158,11,0.25)]'"
                            x-text="confirmText"></button>
                </div>
            </div>
        </div>
    </div>
</div>

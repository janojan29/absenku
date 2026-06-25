@props([
    'name',
    'options' => [],       // array of ['value' => ..., 'label' => ...]
    'selected' => '',      // currently selected value
    'placeholder' => 'Pilih...',
    'onSelect' => '',      // JS callback: e.g. "document.getElementById('form').submit()"
    'wireClick' => '',     // Livewire wire:click pattern: e.g. "set('prop', :value)"
])

@php
    $selectedLabel = $placeholder;
    foreach ($options as $opt) {
        if ((string) $opt['value'] === (string) $selected) {
            $selectedLabel = $opt['label'];
            break;
        }
    }
    
    $optionsJson = json_encode(collect($options)->map(fn($o) => ['value' => (string)$o['value'], 'label' => $o['label']])->values()->all());
@endphp

<div class="relative"
     wire:ignore.self
     wire:key="select-{{ $name }}"
     data-es-value="{{ $selected }}"
     data-es-label="{{ $selectedLabel }}"
     data-es-options='{{ $optionsJson }}'
     x-data="{
         open: false,
         selectedValue: '{{ addslashes((string) $selected) }}',
         selectedLabel: '{{ addslashes($selectedLabel) }}'
     }"
     x-init="
         // Lookup function to update label based on value
         let updateLabel = (val) => {
             let options = JSON.parse($el.getAttribute('data-es-options') || '[]');
             let opt = options.find(o => String(o.value) === String(val));
             selectedLabel = opt ? opt.label : '{{ addslashes($placeholder) }}';
         };

         // Watch selectedValue to update the hidden input and the selectedLabel
         $watch('selectedValue', val => {
             updateLabel(val);
             
             let input = $refs.hiddenInput;
             if (input) {
                 input.value = val;
                 input.setAttribute('value', val);
                 input.dispatchEvent(new Event('change', { bubbles: true }));
                 input.dispatchEvent(new Event('input', { bubbles: true }));
             }
         });
         
         // Initialize label on load
         updateLabel(selectedValue);

         // MutationObserver to watch attribute updates from Livewire (since wire:ignore is present)
         let observer = new MutationObserver((mutations) => {
             mutations.forEach((mutation) => {
                 if (mutation.type === 'attributes') {
                     if (mutation.attributeName === 'data-es-value') {
                         let val = $el.getAttribute('data-es-value') || '';
                         if (String(selectedValue) !== String(val)) {
                             selectedValue = val;
                         }
                     }
                     if (mutation.attributeName === 'data-es-label') {
                         let lbl = $el.getAttribute('data-es-label') || '{{ addslashes($placeholder) }}';
                         if (selectedLabel !== lbl) {
                             selectedLabel = lbl;
                         }
                     }
                 }
             });
         });
         observer.observe($el, { attributes: true, attributeFilter: ['data-es-value', 'data-es-label'] });
     "
     @click.outside="open = false"
     @keydown.escape.window="open = false"
     @reset-select-filters.window="
         selectedValue = '';
         selectedLabel = '{{ addslashes($placeholder) }}';
     ">

    {{-- Hidden input for form submission --}}
    <input type="hidden" name="{{ $name }}" x-ref="hiddenInput" :value="selectedValue">

    {{-- Trigger Button --}}
    <button type="button"
            @click="open = !open"
            class="flex items-center justify-between w-full px-4 py-2.5 text-left text-sm bg-white border border-bw-200 rounded-xl hover:border-bw-300 focus:outline-none transition-all duration-150"
            :class="open ? 'ring-2 ring-navy-500/20 border-navy-500 hover:border-navy-500' : ''">
        <span x-text="selectedLabel" class="truncate text-navy-800"></span>
        <svg class="w-4 h-4 text-bw-400 transition-transform duration-200 shrink-0 ml-2"
             :class="open ? 'rotate-180 text-navy-500' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="absolute left-0 right-0 mt-1.5 w-full bg-white border border-bw-200 rounded-xl shadow-lg overflow-hidden"
         style="display: none; z-index: 9999;">
        <div class="max-h-52 overflow-y-auto overscroll-contain py-1">
            @foreach ($options as $opt)
                <button type="button"
                    @click="
                        selectedValue = '{{ addslashes($opt['value']) }}';
                        open = false;
                        @if ($wireClick)
                            (function() {
                                let wireObj = null;
                                try {
                                    wireObj = $wire;
                                } catch (e) {
                                    let lwEl = $el.closest('[wire\\:id]');
                                    if (lwEl && window.Livewire) {
                                        wireObj = window.Livewire.find(lwEl.getAttribute('wire:id'));
                                    }
                                }
                                if (wireObj) {
                                    wireObj.{{ str_replace(':value', '\'' . addslashes($opt['value']) . '\'', $wireClick) }};
                                } else {
                                    console.error('expandable-select: Livewire instance could not be resolved.');
                                }
                            })();
                        @elseif ($onSelect)
                            $nextTick(() => { {{ $onSelect }} });
                        @endif
                    "
                    class="flex items-center gap-2.5 w-full px-3.5 py-2.5 text-left text-sm transition-all duration-150"
                    :class="String(selectedValue) === '{{ addslashes($opt['value']) }}'
                        ? 'bg-navy-50 text-navy-700 font-semibold'
                        : 'text-navy-600 hover:bg-bw-50'">
                    {{-- Check icon for selected --}}
                    <svg x-show="String(selectedValue) === '{{ addslashes($opt['value']) }}'"
                         class="w-4 h-4 text-navy-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    <span x-show="String(selectedValue) !== '{{ addslashes($opt['value']) }}'" class="w-4 shrink-0"></span>
                    <span class="truncate">{{ $opt['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-2 py-3 w-full">
        {{-- Previous Button (Flush Left) --}}
        <div class="flex-shrink-0">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-xs font-semibold text-bw-400 bg-bw-50 border border-bw-200/60 rounded-lg cursor-not-allowed select-none">
                    {!! __('Sebelumnya') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-navy-700 bg-white border border-bw-200 hover:bg-navy-50/50 rounded-lg transition duration-150 shadow-sm">
                    {!! __('Sebelumnya') !!}
                </a>
            @endif
        </div>

        {{-- Centered Results Description --}}
        <div class="text-center flex-1 min-w-0 px-2">
            <p class="text-xs text-navy-800 font-medium truncate">
                Menampilkan
                @if ($paginator->firstItem())
                    <span class="font-bold text-navy-950">{{ $paginator->firstItem() }}</span>
                    -
                    <span class="font-bold text-navy-950">{{ $paginator->lastItem() }}</span>
                @else
                    <span class="font-bold text-navy-950">{{ $paginator->count() }}</span>
                @endif
                dari
                <span class="font-bold text-navy-950">{{ $paginator->total() }}</span>
                data
            </p>
        </div>

        {{-- Next Button (Flush Right) --}}
        <div class="flex-shrink-0">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-navy-700 bg-white border border-bw-200 hover:bg-navy-50/50 rounded-lg transition duration-150 shadow-sm">
                    {!! __('Selanjutnya') !!}
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-xs font-semibold text-bw-400 bg-bw-50 border border-bw-200/60 rounded-lg cursor-not-allowed select-none">
                    {!! __('Selanjutnya') !!}
                </span>
            @endif
        </div>
    </nav>
@endif

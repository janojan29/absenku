@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-2 items-center justify-between py-2">

        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-4 py-2 text-xs font-semibold text-bw-400 bg-bw-50 border border-bw-200/60 rounded-lg cursor-not-allowed">
                {!! __('Sebelumnya') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-navy-700 bg-white border border-bw-200 hover:bg-navy-50/50 rounded-lg transition duration-150">
                {!! __('Sebelumnya') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-navy-700 bg-white border border-bw-200 hover:bg-navy-50/50 rounded-lg transition duration-150">
                {!! __('Berikutnya') !!}
            </a>
        @else
            <span class="inline-flex items-center px-4 py-2 text-xs font-semibold text-bw-400 bg-bw-50 border border-bw-200/60 rounded-lg cursor-not-allowed">
                {!! __('Berikutnya') !!}
            </span>
        @endif

    </nav>
@endif

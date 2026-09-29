@if ($paginator->hasPages())
<nav class="flex items-center justify-between" role="navigation" aria-label="Pagination Navigation">
    <div class="text-[10px] text-muted">
        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }} results
    </div>
    <div class="flex items-center gap-1">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="flex h-7 w-7 items-center justify-center rounded-md text-slate-600 cursor-not-allowed">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="flex h-7 w-7 items-center justify-center rounded-md text-muted hover:bg-white/5 hover:text-white transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
        @endif

        {{-- Pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="flex h-7 w-7 items-center justify-center text-[11px] text-slate-600">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-indigo-600 text-[11px] font-semibold text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="flex h-7 w-7 items-center justify-center rounded-md text-[11px] text-muted hover:bg-white/5 hover:text-white transition-colors">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="flex h-7 w-7 items-center justify-center rounded-md text-muted hover:bg-white/5 hover:text-white transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        @else
            <span class="flex h-7 w-7 items-center justify-center rounded-md text-slate-600 cursor-not-allowed">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
        @endif
    </div>
</nav>
@endif

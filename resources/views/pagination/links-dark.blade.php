@if ($paginator->hasPages())
<nav class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
    @if ($paginator->onFirstPage())
        <span class="links-page-btn links-page-btn-disabled">&lsaquo; Prev</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="links-page-btn">&lsaquo; Prev</a>
    @endif

    <ul class="pagination mb-0 d-flex align-items-center gap-1 flex-wrap">
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="links-page-dots">{{ $element }}</li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li><span class="links-page-btn links-page-btn-active">{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}" class="links-page-btn">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
    </ul>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="links-page-btn">Next &rsaquo;</a>
    @else
        <span class="links-page-btn links-page-btn-disabled">Next &rsaquo;</span>
    @endif
</nav>
<style>
.links-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    background: rgba(30,30,45,0.6);
    border: 1px solid rgba(167,139,250,0.4);
    border-radius: 8px;
    color: #fff;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}
.links-page-btn:hover { background: rgba(40,40,60,0.8); border-color: rgba(167,139,250,0.6); color: #fff; }
.links-page-btn-active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-color: transparent;
}
.links-page-btn-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.links-page-dots {
    padding: 0 4px;
    color: rgba(255,255,255,0.5);
}
</style>
@endif

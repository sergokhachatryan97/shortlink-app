@extends('layouts.app')

@section('title', 'My Links')

@section('content')
<div class="container" style="max-width: 720px;">
    <h1 class="mb-2 fw-bold" style="font-size: 1.75rem; color: #1e293b;">My Links</h1>
    <p class="text-muted mb-4">All links you generated. Download before your subscription ends.</p>

    @if ($planLimit > 0)
        <p class="small text-muted mb-3">
            <strong>{{ $totalCount }}</strong> / {{ number_format($planLimit) }} links used
            @if ($totalCount >= $planLimit)
                <span class="text-warning">— at plan limit. New links require balance payment.</span>
            @endif
        </p>
    @else
        <p class="small text-muted mb-3"><strong>{{ $totalCount }}</strong> links generated</p>
    @endif

    <div class="mb-3">
        @if ($totalCount > 0)
            <a href="{{ route('links.download') }}" class="btn btn-primary" style="background: linear-gradient(135deg, var(--brand), var(--accent)); border: none; font-weight: 600; border-radius: 10px;">Download all (CSV)</a>
        @else
            <button type="button" class="btn btn-secondary" disabled style="border-radius: 10px;">Download all (CSV)</button>
        @endif
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            @forelse($links as $link)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom gap-2 links-row">
                    <div class="flex-grow-1 min-w-0" title="{{ $link->original_url }}">
                        <a href="{{ $link->short_url }}" target="_blank" rel="noopener" class="text-decoration-none text-truncate d-block">{{ $link->short_url }}</a>
                        <div class="text-muted small text-truncate">{{ Str::limit($link->original_url, 50) }}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-copy flex-shrink-0" data-url="{{ e($link->short_url) }}" title="Copy link">
                        Copy
                    </button>
                </div>
            @empty
                <p class="text-muted mb-0">No links yet. <a href="{{ route('shortlink.index') }}">Generate links</a></p>
            @endforelse
        </div>
    </div>

    @if ($links->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            <div class="links-pagination">
                {{ $links->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
.links-pagination nav { flex-wrap: wrap; }
.links-pagination .pagination { margin-bottom: 0; }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.btn-copy').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const url = this.dataset.url;
        navigator.clipboard.writeText(url).then(function() {
            const orig = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.add('btn-success', 'border-success');
            btn.classList.remove('btn-outline-secondary');
            setTimeout(function() {
                btn.textContent = orig;
                btn.classList.remove('btn-success', 'border-success');
                btn.classList.add('btn-outline-secondary');
            }, 1500);
        });
    });
});
</script>
@endpush
@endsection

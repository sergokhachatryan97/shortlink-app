@extends('layouts.app')

@section('title', 'My Links')

@section('content')
<div class="container" style="max-width: 900px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1">My Links</h1>
            <p class="page-subtitle mb-0">All links you generated. Download before your subscription ends.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <input type="search" id="search-links" class="form-control" placeholder="Search links..." style="width: 200px; min-width: 160px; border-radius: 10px;">
            @if ($totalCount > 0)
                <a href="{{ route('links.download') }}" class="btn btn-primary-gradient px-4 text-nowrap">Export CSV</a>
            @else
                <button type="button" class="btn btn-secondary px-4 text-nowrap" disabled style="border-radius: 10px;">Export CSV</button>
            @endif
        </div>
    </div>

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

    <div class="card card-dashboard overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-0 py-3 px-4" style="font-weight: 600; font-size: 0.8125rem; color: #64748b;">Original URL</th>
                        <th class="border-0 py-3 px-4" style="font-weight: 600; font-size: 0.8125rem; color: #64748b;">Short URL</th>
                        <th class="border-0 py-3 px-4 text-end" style="font-weight: 600; font-size: 0.8125rem; color: #64748b; min-width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="links-tbody">
                    @forelse($links as $link)
                    <tr class="link-row" data-original="{{ Str::lower($link->original_url) }}" data-short="{{ Str::lower($link->short_url) }}">
                        <td class="py-3 px-4">
                            <a href="{{ $link->short_url }}" target="_blank" rel="noopener" class="text-decoration-none text-dark">{{ Str::limit($link->short_url, 40) }}</a>
                            <div class="text-muted small text-truncate mt-0" style="max-width: 280px;">{{ Str::limit($link->original_url, 50) }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <code class="small">{{ Str::limit($link->short_url, 35) }}</code>
                        </td>
                        <td class="py-3 px-4 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" data-url="{{ e($link->short_url) }}" title="Copy link">Copy</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-5 px-4 text-center text-muted">
                            No links yet. <a href="{{ route('shortlink.index') }}">Generate links</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($links->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $links->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@push('styles')
<style>
#search-links:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const search = document.getElementById('search-links');
    const rows = document.querySelectorAll('.link-row');
    if (search && rows.length) {
        search.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            rows.forEach(function(row) {
                const match = !q || row.dataset.original.includes(q) || row.dataset.short.includes(q);
                row.style.display = match ? '' : 'none';
            });
        });
    }
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
})();
</script>
@endpush
@endsection

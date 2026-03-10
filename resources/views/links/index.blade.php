@extends('layouts.app')

@section('title', 'My Links')

@section('content')
<div class="links-page-cosmic">
    <div class="container links-page-container" style="max-width: 900px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="links-page-title mb-1">My Links</h1>
                <p class="links-page-subtitle mb-0">All links you ever generated! Download before your subscription ends.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap">
                <input type="search" id="search-links" class="form-control links-search" placeholder="Search Links...">
                @if ($totalCount > 0)
                    <button type="button" id="copy-all-links" class="btn btn-links-outline">Copy all links</button>
                    <a href="{{ route('links.download') }}" class="btn btn-links-primary">Export CSV</a>
                @else
                    <button type="button" class="btn btn-links-outline" disabled>Copy all links</button>
                    <button type="button" class="btn btn-links-primary" disabled>Export CSV</button>
                @endif
            </div>
        </div>

        @if ($planLimit > 0)
            <p class="links-page-count mb-3">
                <strong>{{ $totalCount }}</strong> / {{ number_format($planLimit) }} links used
                @if ($totalCount >= $planLimit)
                    <span class="text-warning">— at plan limit. New links require balance payment.</span>
                @endif
            </p>
        @else
            <p class="links-page-count mb-3"><strong>{{ $totalCount }}</strong> links generated</p>
        @endif

        <div class="links-table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 links-table">
                    <thead>
                        <tr>
                            <th class="links-th">Original URL</th>
                            <th class="links-th">Short URL</th>
                            <th class="links-th text-end" style="min-width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="links-tbody">
                        @forelse($links as $link)
                        <tr class="link-row" data-original="{{ Str::lower($link->original_url) }}" data-short="{{ Str::lower($link->short_url) }}">
                            <td class="links-td">
                                <div class="links-original" title="{{ $link->original_url }}">{{ $link->original_url }}</div>
                            </td>
                            <td class="links-td">
                                <a href="{{ $link->short_url }}" target="_blank" rel="noopener" class="links-short" title="{{ $link->short_url }}">{{ $link->short_url }}</a>
                            </td>
                            <td class="links-td text-end">
                                <button type="button" class="btn btn-links-copy btn-copy" data-url="{{ e($link->short_url) }}" title="Copy link">Copy</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="links-empty">
                                No links yet. <a href="{{ route('shortlink.index') }}">Generate links</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($links->hasPages())
            <div class="links-pagination-wrap mt-4 d-flex justify-content-center">
                {{ $links->links('pagination.links-dark') }}
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.links-page-cosmic {
    min-height: calc(100vh - var(--navbar-height, 64px) - 80px);
    background: #0a0a12 url('{{ asset('images/hero-bg.png') }}') no-repeat center center;
    background-size: cover;
    margin: -1.5rem 0 0;
    padding: 2rem 1rem 3rem;
    position: relative;
}
.links-page-cosmic::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(10,10,18,0.75) 0%, rgba(10,10,18,0.9) 100%);
    pointer-events: none;
}
.links-page-container { position: relative; z-index: 1; }
.links-page-title { font-size: 1.75rem; font-weight: 700; color: #fff; }
.links-page-subtitle { color: rgba(255,255,255,0.7); font-size: 0.9375rem; }
.links-page-count { color: rgba(255,255,255,0.8); font-size: 0.9375rem; }
.links-search {
    width: 200px; min-width: 160px;
    background: rgba(30,30,45,0.7);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    border-radius: 10px;
    padding: 10px 14px;
}
.links-search::placeholder { color: rgba(255,255,255,0.5); }
.links-search:focus {
    background: rgba(30,30,45,0.8);
    border-color: #a78bfa;
    box-shadow: 0 0 0 3px rgba(167,139,250,0.3);
    color: #fff;
}
.btn-links-outline {
    background: rgba(30,30,45,0.8);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
}
.btn-links-outline:hover { background: rgba(40,40,60,0.9); color: #fff; border-color: rgba(255,255,255,0.3); }
.btn-links-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    color: #fff !important;
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
}
.btn-links-primary:hover { color: #fff !important; opacity: 0.95; }
.links-table-card {
    background: #16162a !important;
    --bs-table-bg: transparent;
    --bs-table-striped-bg: transparent;
    --bs-table-hover-bg: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    overflow: hidden;
}
.links-table-card .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: #16162a !important;
}
.links-table,
.links-table thead,
.links-table tbody,
.links-table tr,
.links-table th,
.links-table td {
    background: transparent !important;
    color: #e2e8f0 !important;
}
.links-table {
    width: 100%;
    min-width: 500px;
    table-layout: fixed;
    margin-bottom: 0 !important;
    border-collapse: collapse;
}
.links-table thead th,
.links-table tbody td {
    border: none !important;
    vertical-align: middle !important;
}
.links-th {
    font-weight: 600;
    font-size: 0.8125rem;
    color: #fff !important;
    background: rgba(0,0,0,0.25) !important;
    border-bottom: 1px solid rgba(255,255,255,0.1) !important;
    padding: 1rem 1.25rem;
}
.links-th:first-child { width: 40%; }
.links-th:nth-child(2) { width: calc(60% - 100px); }
.links-th:last-child { width: 100px; }
.links-td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.08) !important;
    background: transparent !important;
}
.links-table tbody tr:last-child td { border-bottom: none !important; }
.links-table tbody tr:hover { background: rgba(255,255,255,0.04) !important; }
.links-original {
    color: rgba(255,255,255,0.65);
    font-size: 0.9375rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}
.links-short {
    color: #a78bfa;
    font-size: 0.9rem;
    text-decoration: none;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-block;
    max-width: 100%;
}
.links-short:hover { color: #c4b5fd; }
.links-empty { color: rgba(255,255,255,0.6); text-align: center; padding: 3rem !important; }
.links-empty a { color: #a78bfa; }
.btn-links-copy {
    background: rgba(30,30,45,0.8);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.875rem;
    font-weight: 500;
}
.btn-links-copy:hover { background: rgba(40,40,60,0.9); color: #fff; border-color: rgba(255,255,255,0.3); }
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
    const copyAllBtn = document.getElementById('copy-all-links');
    if (copyAllBtn) {
        copyAllBtn.addEventListener('click', function() {
            const btn = copyAllBtn;
            btn.disabled = true;
            fetch('{{ route("links.copy") }}', { credentials: 'same-origin' })
                .then(function(r) { return r.text(); })
                .then(function(text) {
                    const t = text.trim();
                    if (!t) { btn.disabled = false; return; }
                    navigator.clipboard.writeText(t).then(function() {
                        const orig = btn.textContent;
                        btn.textContent = 'Copied!';
                        setTimeout(function() { btn.textContent = orig; btn.disabled = false; }, 1500);
                    }).catch(function() { btn.disabled = false; });
                })
                .catch(function() { btn.disabled = false; });
        });
    }
    document.querySelectorAll('.btn-copy').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            navigator.clipboard.writeText(url).then(function() {
                const orig = btn.textContent;
                btn.textContent = 'Copied!';
                btn.style.background = 'rgba(34,197,94,0.3)';
                btn.style.borderColor = 'rgba(34,197,94,0.6)';
                setTimeout(function() {
                    btn.textContent = orig;
                    btn.style.background = '';
                    btn.style.borderColor = '';
                }, 1500);
            });
        });
    });
})();
</script>
@endpush
@endsection

@extends('layouts.app')

@section('title', 'UTM Builder — Trastly')

@section('content')
<div class="utm-page-section">
    <div class="container utm-container">
        <a href="{{ route('shortlink.index') }}" class="utm-back-link d-inline-flex align-items-center gap-2 mb-3 text-decoration-none">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('messages.utm.back_to_generator') }}
        </a>
        <div class="utm-page-header mb-4">
            <h1 class="utm-page-title">{{ __('messages.utm.title') }}</h1>
            <p class="utm-page-subtitle mb-0">{{ __('messages.utm.subtitle') }}</p>
        </div>

        {{-- Presets Bar --}}
        @if($presets->count())
        <div class="utm-card p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="utm-label">{{ __('messages.utm.presets') }}</span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($presets as $preset)
                @php($presetData = json_encode($preset->only(['id','utm_source','utm_medium','utm_campaign','utm_content','utm_term'])))
                <button type="button" class="btn utm-preset-btn" data-preset="{{ e($presetData) }}">
                    {{ $preset->name }}
                    <span class="utm-preset-del" data-preset-id="{{ $preset->id }}">&times;</span>
                </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Builder Form --}}
        <div class="utm-card p-4 mb-4">
            <form id="utm-form" autocomplete="off">
                @csrf
                {{-- Target URL --}}
                <div class="mb-4">
                    <label for="utm-url" class="utm-label">{{ __('messages.utm.target_url') }} <span class="text-danger">*</span></label>
                    <input type="url" id="utm-url" name="url" required placeholder="https://example.com/product" class="form-control utm-input" value="{{ request('url') }}">
                </div>

                <div class="row g-3 mb-3">
                    {{-- utm_source --}}
                    <div class="col-md-4">
                        <label for="utm-source" class="utm-label">utm_source <span class="text-danger">*</span></label>
                        <input type="text" id="utm-source" name="utm_source" required placeholder="google" class="form-control utm-input" list="source-list">
                        <datalist id="source-list">
                            <option value="google"><option value="yandex"><option value="vk"><option value="facebook"><option value="telegram"><option value="newsletter">
                        </datalist>
                        <div class="utm-quick-tags">
                            <button type="button" class="utm-tag" data-field="utm-source" data-value="google">google</button>
                            <button type="button" class="utm-tag" data-field="utm-source" data-value="yandex">yandex</button>
                            <button type="button" class="utm-tag" data-field="utm-source" data-value="vk">vk</button>
                            <button type="button" class="utm-tag" data-field="utm-source" data-value="facebook">facebook</button>
                            <button type="button" class="utm-tag" data-field="utm-source" data-value="telegram">telegram</button>
                        </div>
                    </div>

                    {{-- utm_medium --}}
                    <div class="col-md-4">
                        <label for="utm-medium" class="utm-label">utm_medium <span class="text-danger">*</span></label>
                        <input type="text" id="utm-medium" name="utm_medium" required placeholder="cpc" class="form-control utm-input" list="medium-list">
                        <datalist id="medium-list">
                            <option value="cpc"><option value="cpm"><option value="social"><option value="email"><option value="banner">
                        </datalist>
                        <div class="utm-quick-tags">
                            <button type="button" class="utm-tag" data-field="utm-medium" data-value="cpc">cpc</button>
                            <button type="button" class="utm-tag" data-field="utm-medium" data-value="cpm">cpm</button>
                            <button type="button" class="utm-tag" data-field="utm-medium" data-value="social">social</button>
                            <button type="button" class="utm-tag" data-field="utm-medium" data-value="email">email</button>
                            <button type="button" class="utm-tag" data-field="utm-medium" data-value="banner">banner</button>
                        </div>
                    </div>

                    {{-- utm_campaign --}}
                    <div class="col-md-4">
                        <label for="utm-campaign" class="utm-label">utm_campaign <span class="text-danger">*</span></label>
                        <input type="text" id="utm-campaign" name="utm_campaign" required placeholder="summer_sale" class="form-control utm-input" list="campaign-list">
                        <datalist id="campaign-list">
                            @foreach($campaigns as $c)
                            <option value="{{ $c }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>

                {{-- Advanced toggle --}}
                <div class="mb-3">
                    <button type="button" class="btn btn-sm utm-toggle-advanced" id="toggle-advanced">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                        {{ __('messages.utm.additional_params') }}
                    </button>
                </div>

                <div id="advanced-fields" style="display:none;">
                    <div class="row g-3 mb-3">
                        {{-- utm_content --}}
                        <div class="col-md-6">
                            <label for="utm-content" class="utm-label">utm_content</label>
                            <div class="input-group">
                                <input type="text" id="utm-content" name="utm_content" placeholder="banner_3" class="form-control utm-input">
                                <button type="button" class="btn utm-macro-btn dropdown-toggle" data-bs-toggle="dropdown">{{ __('messages.utm.macro') }}</button>
                                <ul class="dropdown-menu dropdown-menu-dark utm-macro-menu" data-target="utm-content">
                                    <li class="dropdown-header">Yandex Direct</li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="{campaign_id}">{campaign_id}</a></li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="{position_type}">{position_type}</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">Facebook</li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{campaign.name}}">&#123;&#123;campaign.name&#125;&#125;</a></li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{adset.name}}">&#123;&#123;adset.name&#125;&#125;</a></li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{ad.name}}">&#123;&#123;ad.name&#125;&#125;</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">myTarget</li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{campaign_id}}">&#123;&#123;campaign_id&#125;&#125;</a></li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{banner_id}}">&#123;&#123;banner_id&#125;&#125;</a></li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{geo}}">&#123;&#123;geo&#125;&#125;</a></li>
                                </ul>
                            </div>
                            <div class="utm-quick-tags">
                                <button type="button" class="utm-tag" data-field="utm-content" data-value="button_red">button_red</button>
                                <button type="button" class="utm-tag" data-field="utm-content" data-value="banner_3">banner_3</button>
                                <button type="button" class="utm-tag" data-field="utm-content" data-value="video_a">video_a</button>
                            </div>
                        </div>

                        {{-- utm_term --}}
                        <div class="col-md-6">
                            <label for="utm-term" class="utm-label">utm_term</label>
                            <div class="input-group">
                                <input type="text" id="utm-term" name="utm_term" placeholder="{keyword}" class="form-control utm-input">
                                <button type="button" class="btn utm-macro-btn dropdown-toggle" data-bs-toggle="dropdown">{{ __('messages.utm.macro') }}</button>
                                <ul class="dropdown-menu dropdown-menu-dark utm-macro-menu" data-target="utm-term">
                                    <li class="dropdown-header">Yandex Direct</li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="{keyword}">{keyword}</a></li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="{position_type}">{position_type}</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">Facebook</li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{campaign.name}}">&#123;&#123;campaign.name&#125;&#125;</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">myTarget</li>
                                    <li><a class="dropdown-item utm-macro-item" data-value="@{{geo}}">&#123;&#123;geo&#125;&#125;</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Source domain warning --}}
                <div id="source-warning" class="alert alert-warning py-2 small mb-3" style="display:none;">
                    {{ __('messages.utm.source_warning') }}
                </div>

                {{-- Preview --}}
                <div class="utm-preview-box mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="utm-label mb-0">{{ __('messages.utm.preview') }}</span>
                        <button type="button" id="copy-preview" class="btn btn-sm utm-copy-btn">{{ __('messages.utm.copy') }}</button>
                    </div>
                    <div id="utm-preview" class="utm-preview-url">https://example.com</div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn utm-btn-primary">{{ __('messages.utm.generate') }}</button>
                    <button type="button" id="btn-shorten" class="btn utm-btn-secondary" style="display:none;">{{ __('messages.utm.shorten') }}</button>
                    <button type="button" id="btn-save-preset" class="btn utm-btn-outline">{{ __('messages.utm.save_preset') }}</button>
                </div>

                {{-- Result --}}
                <div id="utm-result" class="utm-result-box mt-3" style="display:none;">
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="utm-result-url" class="form-control utm-input" readonly>
                        <button type="button" id="copy-result" class="btn utm-btn-primary">{{ __('messages.utm.copy') }}</button>
                    </div>
                    <div id="utm-short-result" class="mt-2" style="display:none;">
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="utm-short-url" class="form-control utm-input" readonly>
                            <button type="button" id="copy-short" class="btn utm-btn-primary">{{ __('messages.utm.copy') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- CSV Import --}}
        <div class="utm-card p-4 mb-4">
            <h5 class="utm-card-title mb-3">{{ __('messages.utm.csv_import') }}</h5>
            <p class="utm-text-muted small mb-3">{{ __('messages.utm.csv_desc') }}</p>
            <form id="csv-form" enctype="multipart/form-data">
                @csrf
                <div class="d-flex gap-2 align-items-center">
                    <input type="file" name="csv_file" accept=".csv,.txt" class="form-control utm-input" style="max-width:320px;" required>
                    <button type="submit" class="btn utm-btn-secondary">{{ __('messages.utm.import') }}</button>
                </div>
            </form>
            <div id="csv-results" class="mt-3" style="display:none;">
                <p class="utm-text-muted small"><span id="csv-count">0</span> {{ __('messages.utm.links_generated') }}</p>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm utm-table">
                        <thead><tr><th>{{ __('messages.utm.original_url') }}</th><th>{{ __('messages.utm.final_url') }}</th></tr></thead>
                        <tbody id="csv-results-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- History --}}
        <div class="utm-card p-4">
            <h5 class="utm-card-title mb-3">{{ __('messages.utm.history') }}</h5>

            <form method="GET" action="{{ route('utm.index') }}" class="d-flex gap-2 flex-wrap mb-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.utm.search_placeholder') }}" class="form-control utm-input" style="max-width:220px;">
                <select name="campaign" class="form-select utm-input" style="max-width:180px;">
                    <option value="">{{ __('messages.utm.all_campaigns') }}</option>
                    @foreach($campaigns as $c)
                    <option value="{{ $c }}" {{ request('campaign') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control utm-input" style="max-width:150px;">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control utm-input" style="max-width:150px;">
                <button type="submit" class="btn utm-btn-secondary">{{ __('messages.utm.filter') }}</button>
                @if(request()->hasAny(['search','campaign','date_from','date_to']))
                <a href="{{ route('utm.index') }}" class="btn utm-btn-outline">{{ __('messages.utm.clear') }}</a>
                @endif
            </form>

            @if($history->count())
            <div class="table-responsive">
                <table class="table table-sm utm-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.utm.date') }}</th>
                            <th>{{ __('messages.utm.campaign') }}</th>
                            <th>{{ __('messages.utm.source_medium') }}</th>
                            <th>{{ __('messages.utm.final_url') }}</th>
                            <th>{{ __('messages.utm.short') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $link)
                        <tr>
                            <td class="text-nowrap small">{{ $link->created_at->format('d.m.Y H:i') }}</td>
                            <td class="small">{{ Str::limit($link->utm_campaign, 20) }}</td>
                            <td class="small">{{ $link->utm_source }} / {{ $link->utm_medium }}</td>
                            <td class="small"><span class="text-break" style="max-width:250px;display:inline-block;">{{ Str::limit($link->final_url, 50) }}</span></td>
                            <td class="small">
                                @if($link->short_url)
                                <a href="{{ $link->short_url }}" target="_blank" class="utm-link">{{ Str::limit($link->short_url, 25) }}</a>
                                @else
                                <span class="utm-text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm utm-btn-outline utm-reuse-btn" data-id="{{ $link->id }}">{{ __('messages.utm.reuse') }}</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $history->links() }}
            @else
            <p class="utm-text-muted mb-0">{{ __('messages.utm.no_links_yet') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.utm-page-section { min-height: calc(100vh - var(--navbar-height, 64px) - 80px); background: #0a0a12 url('{{ asset('images/hero-bg.png') }}') no-repeat center center; background-size: cover; margin: -1.5rem 0 0; padding: 2rem 1rem 3rem; position: relative; }
.utm-page-section::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(10,10,18,0.75) 0%, rgba(10,10,18,0.9) 100%); pointer-events: none; }
.utm-container { position: relative; z-index: 1; max-width: 900px; }
.utm-page-title { font-size: 1.75rem; font-weight: 700; color: #fff; }
.utm-page-subtitle { color: rgba(255,255,255,0.7); font-size: 0.9375rem; }
.utm-back-link { color: #a78bfa; font-weight: 500; font-size: 0.9375rem; }
.utm-back-link:hover { color: #c4b5fd; }
.page-link { background: rgba(30,30,45,0.8) !important; border-color: rgba(255,255,255,0.1) !important; color: #a78bfa !important; }
.page-link:hover { background: rgba(167,139,250,0.2) !important; color: #fff !important; }
.page-item.active .page-link { background: #6366f1 !important; border-color: #6366f1 !important; color: #fff !important; }
.page-item.disabled .page-link { background: rgba(30,30,45,0.5) !important; color: rgba(255,255,255,0.3) !important; }
.utm-card { background: rgba(30, 30, 45, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.utm-card-title { color: #fff; font-weight: 600; margin: 0; }
.utm-label { display: block; color: rgba(255,255,255,0.8); font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.375rem; }
.utm-input { background: rgba(30,30,45,0.8) !important; border: 1px solid rgba(167,139,250,0.3) !important; color: #fff !important; border-radius: 8px; font-size: 0.875rem; }
.utm-input:focus { background: rgba(30,30,45,0.9) !important; border-color: #a78bfa !important; box-shadow: 0 0 0 3px rgba(167,139,250,0.2); color: #fff !important; }
.utm-input::placeholder { color: rgba(255,255,255,0.35) !important; }
.utm-text-muted { color: rgba(255,255,255,0.55); }
.utm-link { color: #a78bfa; text-decoration: none; }
.utm-link:hover { color: #c4b5fd; }
.utm-quick-tags { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 6px; }
.utm-tag { background: rgba(167,139,250,0.15); border: 1px solid rgba(167,139,250,0.25); color: #c4b5fd; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; cursor: pointer; transition: all 0.15s; }
.utm-tag:hover { background: rgba(167,139,250,0.3); border-color: rgba(167,139,250,0.5); color: #fff; }
.utm-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; color: #fff !important; font-weight: 600; padding: 8px 20px; border-radius: 8px; font-size: 0.875rem; }
.utm-btn-primary:hover { opacity: 0.9; color: #fff !important; }
.utm-btn-secondary { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff !important; font-weight: 500; padding: 8px 20px; border-radius: 8px; font-size: 0.875rem; }
.utm-btn-secondary:hover { background: rgba(255,255,255,0.15); color: #fff !important; }
.utm-btn-outline { background: transparent; border: 1px solid rgba(167,139,250,0.4); color: #a78bfa; font-weight: 500; padding: 6px 14px; border-radius: 8px; font-size: 0.8125rem; }
.utm-btn-outline:hover { background: rgba(167,139,250,0.15); color: #c4b5fd; }
.utm-copy-btn { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: rgba(255,255,255,0.7); font-size: 0.75rem; padding: 3px 10px; border-radius: 6px; }
.utm-copy-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }
.utm-macro-btn { background: rgba(167,139,250,0.2); border: 1px solid rgba(167,139,250,0.3); color: #c4b5fd; font-size: 0.8125rem; }
.utm-macro-btn:hover { background: rgba(167,139,250,0.3); color: #fff; }
.utm-macro-menu { background: #1e1e2d; border: 1px solid rgba(167,139,250,0.3); }
.utm-macro-menu .dropdown-item { color: rgba(255,255,255,0.85); font-size: 0.8125rem; }
.utm-macro-menu .dropdown-item:hover { background: rgba(167,139,250,0.2); color: #fff; }
.utm-macro-menu .dropdown-header { color: #a78bfa; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; }
.utm-preview-box { padding: 1rem; background: rgba(0,0,0,0.25); border: 1px solid rgba(167,139,250,0.15); border-radius: 10px; }
.utm-preview-url { color: #86efac; font-family: monospace; font-size: 0.8125rem; word-break: break-all; line-height: 1.5; }
.utm-result-box { padding: 1rem; background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25); border-radius: 10px; }
.utm-toggle-advanced { color: rgba(255,255,255,0.6); font-size: 0.8125rem; background: none; border: none; padding: 0; display: flex; align-items: center; gap: 4px; }
.utm-toggle-advanced:hover { color: #fff; }
.utm-preset-btn { background: rgba(167,139,250,0.15); border: 1px solid rgba(167,139,250,0.3); color: #c4b5fd; font-size: 0.8125rem; padding: 4px 12px; border-radius: 6px; display: flex; align-items: center; gap: 6px; }
.utm-preset-btn:hover { background: rgba(167,139,250,0.25); color: #fff; }
.utm-preset-del { opacity: 0.5; font-size: 1rem; line-height: 1; cursor: pointer; }
.utm-preset-del:hover { opacity: 1; color: #fca5a5; }
.utm-table { color: rgba(255,255,255,0.85) !important; --bs-table-bg: transparent !important; --bs-table-striped-bg: transparent !important; --bs-table-hover-bg: rgba(255,255,255,0.05) !important; --bs-table-color: rgba(255,255,255,0.85) !important; }
.utm-table th { color: rgba(255,255,255,0.5) !important; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom-color: rgba(255,255,255,0.1) !important; background: transparent !important; }
.utm-table td { border-bottom-color: rgba(255,255,255,0.06) !important; vertical-align: middle; background: transparent !important; color: rgba(255,255,255,0.85) !important; }
.utm-table .btn { white-space: nowrap; }
.utm-table a { color: #a78bfa; }
.utm-table a:hover { color: #c4b5fd; }
.form-select.utm-input { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a78bfa' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); }
input[type="date"].utm-input::-webkit-calendar-picker-indicator { filter: invert(0.7); }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const fields = ['utm-url', 'utm-source', 'utm-medium', 'utm-campaign', 'utm-content', 'utm-term'];
    const preview = document.getElementById('utm-preview');
    let lastGeneratedId = null;

    // Transliteration map
    const TRANSLIT = {a:"\u0430",b:"\u0431",v:"\u0432",g:"\u0433",d:"\u0434",e:"\u0435",yo:"\u0451",zh:"\u0436",z:"\u0437",i:"\u0438",y:"\u0439",k:"\u043a",l:"\u043b",m:"\u043c",n:"\u043d",o:"\u043e",p:"\u043f",r:"\u0440",s:"\u0441",t:"\u0442",u:"\u0443",f:"\u0444",kh:"\u0445",ts:"\u0446",ch:"\u0447",sh:"\u0448",shch:"\u0449",y:"\u044b",e:"\u044d",yu:"\u044e",ya:"\u044f"};
    const TRANSLIT_MAP = {};
    // Build reverse: cyrillic -> latin
    const CYR = "\u0430\u0431\u0432\u0433\u0434\u0435\u0451\u0436\u0437\u0438\u0439\u043a\u043b\u043c\u043d\u043e\u043f\u0440\u0441\u0442\u0443\u0444\u0445\u0446\u0447\u0448\u0449\u044a\u044c\u044b\u044d\u044e\u044f";
    const LAT = ["a","b","v","g","d","e","yo","zh","z","i","y","k","l","m","n","o","p","r","s","t","u","f","kh","ts","ch","sh","shch","","","y","e","yu","ya"];
    for (let i = 0; i < CYR.length; i++) { TRANSLIT_MAP[CYR[i]] = LAT[i]; TRANSLIT_MAP[CYR[i].toUpperCase()] = LAT[i]; }

    function transliterate(str) {
        return str.split('').map(c => TRANSLIT_MAP[c] !== undefined ? TRANSLIT_MAP[c] : c).join('');
    }

    function smartClean(val) {
        val = transliterate(val);
        val = val.toLowerCase();
        val = val.replace(/\s+/g, '_');
        val = val.replace(/[^a-z0-9_\-{}.]/g, '');
        return val;
    }

    function buildPreviewUrl() {
        const url = document.getElementById('utm-url').value.trim();
        if (!url) { preview.textContent = 'https://example.com'; return; }
        const params = {};
        ['utm-source','utm-medium','utm-campaign','utm-content','utm-term'].forEach(id => {
            const v = document.getElementById(id).value.trim();
            if (v) params[id.replace('utm-', 'utm_')] = v;
        });
        const qs = new URLSearchParams(params).toString();
        if (!qs) { preview.textContent = url; return; }
        preview.textContent = url + (url.includes('?') ? '&' : '?') + qs;
    }

    // Live preview on input
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', buildPreviewUrl);
    });

    // Smart cleanup on blur for utm fields
    ['utm-source','utm-medium','utm-campaign','utm-content','utm-term'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('blur', function() {
            // Don't clean if contains macros
            if (this.value.includes('{') || this.value.includes('}')) return;
            this.value = smartClean(this.value);
            buildPreviewUrl();
        });
    });

    // Quick tags
    document.querySelectorAll('.utm-tag').forEach(btn => {
        btn.addEventListener('click', function() {
            const field = document.getElementById(this.dataset.field);
            if (field) { field.value = this.dataset.value; buildPreviewUrl(); }
        });
    });

    // Macro insertion
    document.querySelectorAll('.utm-macro-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.closest('.utm-macro-menu');
            const targetId = menu.dataset.target;
            const field = document.getElementById(targetId);
            if (field) {
                const start = field.selectionStart;
                const end = field.selectionEnd;
                const val = field.value;
                field.value = val.substring(0, start) + this.dataset.value + val.substring(end);
                field.focus();
                buildPreviewUrl();
            }
        });
    });

    // Toggle advanced
    document.getElementById('toggle-advanced')?.addEventListener('click', function() {
        const adv = document.getElementById('advanced-fields');
        const open = adv.style.display !== 'none';
        adv.style.display = open ? 'none' : 'block';
        this.querySelector('svg').style.transform = open ? '' : 'rotate(180deg)';
    });

    // Source domain warning
    document.getElementById('utm-source')?.addEventListener('input', function() {
        const url = document.getElementById('utm-url').value;
        const warn = document.getElementById('source-warning');
        try {
            const host = new URL(url).hostname.replace('www.', '');
            warn.style.display = this.value.toLowerCase().includes(host) ? 'block' : 'none';
        } catch { warn.style.display = 'none'; }
    });

    // Copy buttons
    function copyText(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.textContent;
            btn.textContent = @json(__('messages.utm.copied'));
            setTimeout(() => btn.textContent = orig, 1500);
        });
    }
    document.getElementById('copy-preview')?.addEventListener('click', function() { copyText(preview.textContent, this); });
    document.getElementById('copy-result')?.addEventListener('click', function() { copyText(document.getElementById('utm-result-url').value, this); });
    document.getElementById('copy-short')?.addEventListener('click', function() { copyText(document.getElementById('utm-short-url').value, this); });

    // Generate
    document.getElementById('utm-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('[type="submit"]');
        btn.disabled = true; btn.textContent = @json(__('messages.utm.generating'));
        try {
            const body = {};
            ['url','utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(k => {
                const el = this.querySelector('[name="'+k+'"]');
                if (el && el.value.trim()) body[k] = el.value.trim();
            });
            const res = await fetch('{{ route("utm.generate") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(body), credentials: 'same-origin'
            });
            const data = await res.json();
            if (!res.ok) { alert(data.message || 'Error'); return; }
            lastGeneratedId = data.id;
            document.getElementById('utm-result-url').value = data.final_url;
            document.getElementById('utm-result').style.display = 'block';
            document.getElementById('btn-shorten').style.display = 'inline-flex';
        } catch (err) { alert(err.message); }
        finally { btn.disabled = false; btn.textContent = @json(__('messages.utm.generate')); }
    });

    // Shorten
    document.getElementById('btn-shorten')?.addEventListener('click', async function() {
        if (!lastGeneratedId) return;
        this.disabled = true; this.textContent = @json(__('messages.utm.shortening'));
        try {
            const res = await fetch('{{ route("utm.shorten") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id: lastGeneratedId }), credentials: 'same-origin'
            });
            const data = await res.json();
            if (data.short_url) {
                document.getElementById('utm-short-url').value = data.short_url;
                document.getElementById('utm-short-result').style.display = 'block';
            }
        } catch (err) { alert(err.message); }
        finally { this.disabled = false; this.textContent = @json(__('messages.utm.shorten')); }
    });

    // Save preset
    document.getElementById('btn-save-preset')?.addEventListener('click', async function() {
        const name = prompt(@json(__('messages.utm.preset_name')));
        if (!name) return;
        const body = { name };
        ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(k => {
            const el = document.querySelector('[name="'+k+'"]');
            if (el && el.value.trim()) body[k] = el.value.trim();
        });
        await fetch('{{ route("utm.presets.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body), credentials: 'same-origin'
        });
        location.reload();
    });

    // Apply preset
    document.querySelectorAll('.utm-preset-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (e.target.classList.contains('utm-preset-del')) return;
            const p = JSON.parse(this.dataset.preset);
            ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(k => {
                const el = document.getElementById(k.replace('_', '-'));
                if (el) el.value = p[k] || '';
            });
            if (p.utm_content || p.utm_term) {
                document.getElementById('advanced-fields').style.display = 'block';
            }
            buildPreviewUrl();
        });
    });

    // Delete preset
    document.querySelectorAll('.utm-preset-del').forEach(del => {
        del.addEventListener('click', async function(e) {
            e.stopPropagation();
            if (!confirm(@json(__('messages.utm.delete_preset')))) return;
            await fetch('/utm/presets/' + this.dataset.presetId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            this.closest('.utm-preset-btn').remove();
        });
    });

    // Reuse
    document.querySelectorAll('.utm-reuse-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const res = await fetch('/utm/reuse/' + this.dataset.id, {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            });
            const data = await res.json();
            document.getElementById('utm-url').value = data.url || '';
            ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(k => {
                const el = document.getElementById(k.replace('_', '-'));
                if (el) el.value = data[k] || '';
            });
            if (data.utm_content || data.utm_term) {
                document.getElementById('advanced-fields').style.display = 'block';
            }
            buildPreviewUrl();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // CSV Import
    document.getElementById('csv-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = this.querySelector('[type="submit"]');
        btn.disabled = true; btn.textContent = @json(__('messages.utm.importing'));
        try {
            const res = await fetch('{{ route("utm.csv-import") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: formData, credentials: 'same-origin'
            });
            const data = await res.json();
            if (!res.ok) { alert(data.error || data.message || 'Error'); return; }
            document.getElementById('csv-count').textContent = data.count;
            const tbody = document.getElementById('csv-results-body');
            tbody.innerHTML = '';
            data.results.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td class="small">' + r.original_url + '</td><td class="small text-break">' + r.final_url + '</td>';
                tbody.appendChild(tr);
            });
            document.getElementById('csv-results').style.display = 'block';
        } catch (err) { alert(err.message); }
        finally { btn.disabled = false; btn.textContent = @json(__('messages.utm.import')); }
    });
})();
</script>
@endpush

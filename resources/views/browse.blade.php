@extends('layouts.guest')
@section('title', 'Browse Properties')
@section('content')
<div class="min-h-screen bg-zinc-50">

    {{-- Topbar --}}
    <header class="bg-white border-b border-zinc-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('browse') }}" class="text-lg font-semibold text-zinc-900">RealEstate</a>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}"
                   class="btn text-zinc-700 hover:text-zinc-900">
                    Sign In
                </a>
                <a href="{{ route('register') }}"
                   class="btn btn-primary">
                    Register
                </a>
            </div>
        </div>
    </header>

    {{-- Hero / search strip --}}
    <div class="bg-indigo-600 py-12 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl font-bold text-white mb-2">Find your next property</h1>
            <p class="text-indigo-200 text-sm mb-6">Browse listings from agencies across the UAE</p>
            <div class="flex flex-wrap gap-2 max-w-xl mx-auto">
                <input type="search" id="browse-q" aria-label="Search properties"
                       placeholder="Search by title, city, or address…"
                       class="flex-1 px-4 py-2.5 text-sm rounded-lg border border-transparent focus:outline-none focus:ring-2 focus:ring-white/60 bg-white text-zinc-900 placeholder:text-zinc-400">
                <button type="button" id="browse-search-btn"
                        class="px-5 py-2.5 text-sm font-medium text-indigo-600 bg-white hover:bg-indigo-50 rounded-lg transition-colors">
                    Search
                </button>
                <button type="button" id="browse-clear"
                        class="hidden px-4 py-2.5 text-sm font-medium text-white/70 hover:text-white transition-colors whitespace-nowrap">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Filter row --}}
    <div class="bg-white border-b border-zinc-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-3 py-3">
                <select id="browse-type" aria-label="Property type"
                        class="input input-sm">
                    <option value="">All types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                    @endforeach
                </select>

                <input type="text" id="browse-city" aria-label="City"
                       placeholder="City"
                       class="input input-sm w-full sm:w-36">

                <input type="number" id="browse-min-price" aria-label="Minimum price in AED" min="0" inputmode="numeric"
                       placeholder="Min price (AED)"
                       class="input input-sm w-[calc(50%-0.375rem)] sm:w-40">

                <input type="number" id="browse-max-price" aria-label="Maximum price in AED" min="0" inputmode="numeric"
                       placeholder="Max price (AED)"
                       class="input input-sm w-[calc(50%-0.375rem)] sm:w-40">
            </div>
        </div>
    </div>

    {{-- Main content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Results count --}}
        <div class="mb-6">
            <p id="browse-count" class="text-sm text-zinc-500" role="status" aria-live="polite">Loading properties…</p>
        </div>

        {{-- Loading skeleton (shown on initial load and during each fetch) --}}
        <div id="browse-loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @for($i = 0; $i < 6; $i++)
            <div class="bg-white rounded-xl overflow-hidden shadow-sm animate-pulse">
                <div class="aspect-video bg-zinc-200"></div>
                <div class="p-4 space-y-3">
                    <div class="h-4 bg-zinc-200 rounded w-3/4"></div>
                    <div class="h-3 bg-zinc-200 rounded w-1/2"></div>
                    <div class="h-3 bg-zinc-200 rounded w-1/3"></div>
                    <div class="h-8 bg-zinc-200 rounded mt-4"></div>
                </div>
            </div>
            @endfor
        </div>

        {{-- Property grid (JS-populated) --}}
        <div id="browse-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" style="display:none"></div>

        {{-- Empty state --}}
        <div id="browse-empty" style="display:none" class="text-center py-24">
            <svg class="w-16 h-16 text-zinc-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <h2 class="text-zinc-700 font-semibold text-sm mb-1">No properties match your search</h2>
            <p class="text-zinc-400 text-sm mb-4">Try adjusting your filters or search term.</p>
            <button type="button" id="browse-empty-clear" class="px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                Clear all filters →
            </button>
        </div>

        {{-- Pagination (JS-populated) --}}
        <div id="browse-pagination" class="mt-8 flex flex-wrap justify-center gap-2" role="navigation" aria-label="Pagination"></div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-zinc-200 mt-16 py-8 text-center text-xs text-zinc-400">
        © {{ date('Y') }} RealEstate Platform. All rights reserved.
    </footer>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var state = { page: 1, q: '', type: '', city: '', min_price: '', max_price: '' };
    var timer = null;
    var REGISTER_URL = '{{ route("register") }}';
    var CAMERA_ICON  = '<svg class="w-10 h-10 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>';

    // ── Helpers ──────────────────────────────────────────────────────────────

    function imgSrc(path) {
        if (!path) return null;
        return path.indexOf('http') === 0 ? path : '/storage/' + path;
    }

    function hasFilter() {
        return state.q || state.type || state.city || state.min_price || state.max_price;
    }

    function syncClearBtn() {
        if (hasFilter()) {
            $('#browse-clear').removeClass('hidden');
        } else {
            $('#browse-clear').addClass('hidden');
        }
    }

    function resetState() {
        state = { page: 1, q: '', type: '', city: '', min_price: '', max_price: '' };
        $('#browse-q').val('');
        $('#browse-type').val('');
        $('#browse-city').val('');
        $('#browse-min-price').val('');
        $('#browse-max-price').val('');
    }

    // ── Card builder ─────────────────────────────────────────────────────────

    function buildCard(prop) {
        var src = imgSrc(prop.image);
        var statusColors = {
            available: 'bg-emerald-50 text-emerald-700',
            reserved:  'bg-amber-50 text-amber-700',
            sold:      'bg-rose-50 text-rose-700'
        };
        var statusClass = statusColors[prop.status] || 'bg-zinc-100 text-zinc-600';
        var inquireUrl  = REGISTER_URL + '?redirect=' + encodeURIComponent(window.location.pathname);

        var imgHtml;
        if (src) {
            imgHtml = '<img src="' + esc(src) + '" alt="' + esc(prop.title) + '" class="w-full h-full object-cover"' +
                      ' onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">' +
                      '<div class="absolute inset-0 bg-zinc-100 items-center justify-center" style="display:none">' + CAMERA_ICON + '</div>';
        } else {
            imgHtml = '<div class="absolute inset-0 flex items-center justify-center">' + CAMERA_ICON + '</div>';
        }

        var stats = [];
        if (prop.bedrooms)  stats.push(prop.bedrooms + ' bed');
        if (prop.bathrooms) stats.push(prop.bathrooms + ' bath');
        if (prop.area_sqft) stats.push(Number(prop.area_sqft).toLocaleString() + ' sqft');

        return '<div class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">' +
            '<div class="relative aspect-video bg-zinc-100">' + imgHtml +
            '<span class="absolute top-2 right-2 px-2 py-0.5 text-xs font-medium rounded-full ' + statusClass + '">' + esc(prop.status) + '</span>' +
            (prop.is_featured ? '<span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-600 text-white">Featured</span>' : '') +
            '</div>' +
            '<div class="p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-900 mb-1 truncate">' + esc(prop.title) + '</h3>' +
            '<p class="text-xs text-zinc-500 mb-1">' + esc(prop.city) + ' · ' + esc(prop.type) + '</p>' +
            (stats.length
                ? '<div class="flex items-center gap-3 text-xs text-zinc-400 mb-3"><span>' + stats.join('</span><span>') + '</span></div>'
                : '<div class="mb-3"></div>') +
            '<div class="flex items-center justify-between mb-3">' +
            '<span class="text-indigo-600 font-semibold text-sm">AED ' + Number(prop.price).toLocaleString() + '</span>' +
            (prop.agency ? '<span class="text-xs text-zinc-400 truncate max-w-[120px]">' + esc(prop.agency.name) + '</span>' : '') +
            '</div>' +
            '<div class="flex gap-2">' +
            '<a href="' + esc(inquireUrl) + '" class="btn btn-primary btn-sm flex-1 text-xs">Inquire</a>' +
            '<a href="' + esc(inquireUrl) + '" class="btn btn-sm flex-1 text-xs text-zinc-700 bg-zinc-100 hover:bg-zinc-200">Book Viewing</a>' +
            '</div>' +
            '</div></div>';
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    function buildPagination(p) {
        var $pg = $('#browse-pagination').empty();
        if (!p || p.last_page <= 1) return;

        var btnBase   = 'px-3 py-1.5 text-sm border border-zinc-200 rounded-lg transition-colors ';
        var btnNormal = btnBase + 'bg-white hover:bg-zinc-50 text-zinc-700';
        var btnActive = btnBase + 'bg-indigo-600 border-indigo-600 text-white cursor-default';
        var btnDisabled = btnBase + 'bg-white text-zinc-300 cursor-not-allowed';

        // Prev
        $('<button class="' + (p.current_page === 1 ? btnDisabled : btnNormal) + '">← Prev</button>')
            .prop('disabled', p.current_page === 1)
            .on('click', function () { if (state.page > 1) { state.page--; load(); } })
            .appendTo($pg);

        // Page numbers (sliding window of 7)
        var start = Math.max(1, p.current_page - 3);
        var end   = Math.min(p.last_page, start + 6);
        start     = Math.max(1, end - 6);

        for (var i = start; i <= end; i++) {
            (function (pg) {
                var cls = (pg === p.current_page) ? btnActive : btnNormal;
                $('<button class="' + cls + '">' + pg + '</button>')
                    .prop('disabled', pg === p.current_page)
                    .on('click', function () { state.page = pg; load(); })
                    .appendTo($pg);
            })(i);
        }

        // Next
        $('<button class="' + (p.current_page === p.last_page ? btnDisabled : btnNormal) + '">Next →</button>')
            .prop('disabled', p.current_page === p.last_page)
            .on('click', function () { if (state.page < p.last_page) { state.page++; load(); } })
            .appendTo($pg);
    }

    // ── Main fetch ───────────────────────────────────────────────────────────

    function load() {
        syncClearBtn();
        $('#browse-loading').show();
        $('#browse-grid').hide().empty();
        $('#browse-empty').hide();
        $('#browse-pagination').empty();

        var params = {};
        if (state.q)         params.q         = state.q;
        if (state.type)      params.type      = state.type;
        if (state.city)      params.city      = state.city;
        if (state.min_price) params.min_price = state.min_price;
        if (state.max_price) params.max_price = state.max_price;
        if (state.page > 1)  params.page      = state.page;

        $.ajax({ url: '/api/browse', method: 'GET', data: params })
        .done(function (res) {
            if (!res.success) return;
            var p = res.pagination;

            if (p && p.total > 0) {
                $('#browse-count').text(
                    'Showing ' + p.from + '–' + p.to + ' of ' + p.total + ' properties'
                );
            } else {
                $('#browse-count').text('No properties found');
            }

            $('#browse-loading').hide();

            if (!res.data || res.data.length === 0) {
                $('#browse-empty').show();
                return;
            }

            var html = '';
            $.each(res.data, function (i, prop) { html += buildCard(prop); });
            $('#browse-grid').html(html).show();

            buildPagination(p);

            if (state.page > 1) {
                $('html, body').animate({ scrollTop: 0 }, 200);
            }
        })
        .fail(function () {
            $('#browse-loading').hide();
            $('#browse-count').text('Failed to load properties. Please refresh the page.');
        });
    }

    // ── Input bindings ───────────────────────────────────────────────────────

    // Hero search (debounced 300ms)
    $('#browse-q').on('input', function () {
        var val = $(this).val();
        clearTimeout(timer);
        timer = setTimeout(function () { state.q = val; state.page = 1; load(); }, 300);
    });

    // Enter key fires immediately
    $('#browse-q').on('keydown', function (e) {
        if (e.key === 'Enter') {
            clearTimeout(timer);
            state.q = $(this).val();
            state.page = 1;
            load();
        }
    });

    // Search button fires immediately
    $('#browse-search-btn').on('click', function () {
        clearTimeout(timer);
        state.q = $('#browse-q').val();
        state.page = 1;
        load();
    });

    // Type dropdown (instant)
    $('#browse-type').on('change', function () {
        state.type = $(this).val();
        state.page = 1;
        load();
    });

    // City (debounced 400ms)
    $('#browse-city').on('input', function () {
        var val = $(this).val();
        clearTimeout(timer);
        timer = setTimeout(function () { state.city = val; state.page = 1; load(); }, 400);
    });

    // Price range (on blur / change)
    $('#browse-min-price, #browse-max-price').on('change', function () {
        state.min_price = $('#browse-min-price').val();
        state.max_price = $('#browse-max-price').val();
        state.page = 1;
        load();
    });

    // Clear buttons (hero + empty state)
    function doClear() {
        clearTimeout(timer);
        resetState();
        load();
    }
    $('#browse-clear, #browse-empty-clear').on('click', doClear);

    // ── Boot ─────────────────────────────────────────────────────────────────
    load();
});
</script>
@endpush

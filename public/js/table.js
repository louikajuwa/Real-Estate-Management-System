// Shared windowed pager: ← 1 … 4 [5] 6 … 20 →. Buttons carry data-page; callers delegate clicks.
window.renderPager = function ($el, p) {
    $el.empty();
    var cur = p.current_page, last = p.last_page;
    if (!last || last < 1) return;
    function btn(label, page, o) {
        o = o || {};
        return '<button type="button" class="min-w-[2rem] px-2.5 py-1 text-xs rounded transition-colors ' +
            (o.active ? 'bg-indigo-600 text-white' : 'text-zinc-600 hover:bg-zinc-100') +
            (o.disabled ? ' opacity-40 cursor-not-allowed' : '') + '" ' +
            (o.disabled ? 'disabled' : 'data-page="' + page + '"') +
            (o.active ? ' aria-current="page"' : '') +
            (o.aria ? ' aria-label="' + o.aria + '"' : '') + '>' + label + '</button>';
    }
    var show = {};
    [1, last].forEach(function (n) { show[n] = true; });
    for (var i = cur - 2; i <= cur + 2; i++) { if (i >= 1 && i <= last) show[i] = true; }
    var nums = Object.keys(show).map(Number).sort(function (a, b) { return a - b; });
    var html = btn('←', cur - 1, { disabled: cur <= 1, aria: 'Previous page' });
    var prev = 0;
    nums.forEach(function (n) {
        if (prev && n - prev === 2) html += btn(prev + 1, prev + 1, { aria: 'Page ' + (prev + 1) });
        else if (prev && n - prev > 2) html += '<span class="px-1 text-xs text-zinc-400" aria-hidden="true">…</span>';
        html += btn(n, n, { active: n === cur, aria: 'Page ' + n });
        prev = n;
    });
    html += btn('→', cur + 1, { disabled: cur >= last, aria: 'Next page' });
    $el.html(html);
};

window.Table = function(config) {
    // config: { container, endpoint, columns, actions, defaultSort, defaultDir, perPage }
    var self = this;
    self.state = {
        page: 1,
        perPage: config.perPage || 15,
        sort: config.defaultSort || 'id',
        dir: config.defaultDir || 'desc',
        search: '',
        filters: {}
    };

    self.init = function() {
        self.render();
        self.load();
        self.bindEvents();
    };

    self.render = function() {
        var $c = $(config.container);
        $c.html(
            '<div class="card overflow-hidden">' +
            '  <div class="px-5 py-4 border-b border-zinc-100 flex flex-wrap items-center gap-3">' +
            '    <div class="relative flex-1 max-w-xs">' +
            '      <input type="search" class="table-search input input-sm w-full pl-8 pr-3 bg-zinc-50" placeholder="Search…" aria-label="Search">' +
            '      <svg class="absolute left-2.5 top-2 w-3.5 h-3.5 text-zinc-400 pointer-events-none" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>' +
            '    </div>' +
            '    <div class="table-header-actions ml-auto"></div>' +
            '  </div>' +
            '  <div class="overflow-x-auto">' +
            '    <table class="w-full text-sm">' +
            '      <thead><tr class="border-b border-zinc-100">' + self.renderHeaders() + '</tr></thead>' +
            '      <tbody id="table-body-' + config.id + '" class="divide-y divide-zinc-50"></tbody>' +
            '    </table>' +
            '  </div>' +
            '  <div class="px-5 py-3 border-t border-zinc-100 flex flex-wrap items-center justify-between gap-2" id="table-pagination-' + config.id + '">' +
            '    <span class="text-xs text-zinc-500" id="table-info-' + config.id + '"></span>' +
            '    <div class="flex flex-wrap items-center gap-1" role="navigation" aria-label="Pagination" id="table-pages-' + config.id + '"></div>' +
            '  </div>' +
            '</div>'
        );
        if (config.headerActions) {
            $c.find('.table-header-actions').html(config.headerActions);
        }
    };

    self.renderHeaders = function() {
        return config.columns.map(function(col) {
            if (!col.sortable) return '<th class="px-5 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">' + (col.label || '') + '</th>';
            return '<th class="px-5 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide cursor-pointer hover:text-zinc-700 select-none" tabindex="0" role="button" data-sort="' + col.key + '">' +
                col.label + '<span class="sort-indicator ml-1 text-zinc-300"></span></th>';
        }).join('') + (config.actions ? '<th class="px-5 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wide">Actions</th>' : '');
    };

    self.load = function() {
        var $body = $('#table-body-' + config.id);
        // Skeleton
        var skeletonRow = '<tr>' + config.columns.map(function() {
            return '<td class="px-5 py-3"><div class="skeleton h-3 rounded w-3/4"></div></td>';
        }).join('') + (config.actions ? '<td></td>' : '') + '</tr>';
        $body.html(skeletonRow.repeat(3));

        var params = $.extend({
            page: self.state.page,
            per_page: self.state.perPage,
            sort: self.state.sort,
            dir: self.state.dir,
            q: self.state.search
        }, self.state.filters);

        api.get(config.endpoint, params).done(function(res) {
            self.renderRows(res.data || []);
            if (res.pagination) self.renderPagination(res.pagination);
        }).fail(function() {
            $body.html('<tr><td colspan="' + (config.columns.length + 1) + '" class="px-5 py-8 text-center text-sm text-zinc-400">Failed to load data.</td></tr>');
            toast.error('Failed to load data.');
        });
    };

    self.renderRows = function(rows) {
        var $body = $('#table-body-' + config.id);
        if (!rows.length) {
            var emptyHtml = config.empty ||
                '<tr><td colspan="' + (config.columns.length + (config.actions ? 1 : 0)) + '" class="px-5 py-12 text-center">' +
                '<div class="flex flex-col items-center gap-2">' +
                '<svg class="w-10 h-10 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>' +
                '<p class="text-sm font-semibold text-zinc-700">No records found</p>' +
                '<p class="text-xs text-zinc-400">Try adjusting your search or filters</p>' +
                '</div></td></tr>';
            $body.html(emptyHtml);
            return;
        }
        var html = rows.map(function(row) {
            var cells = config.columns.map(function(col) {
                var val = col.render ? col.render(row) : (row[col.key] !== null && row[col.key] !== undefined ? esc(String(row[col.key])) : '<span class="text-zinc-300">—</span>');
                return '<td class="px-5 py-3 text-sm text-zinc-700">' + val + '</td>';
            }).join('');
            var actions = config.actions ? '<td class="px-5 py-3 text-right">' + config.actions(row) + '</td>' : '';
            return '<tr class="hover:bg-zinc-50 transition-colors">' + cells + actions + '</tr>';
        }).join('');
        $body.html(html);
    };

    self.renderPagination = function(p) {
        $('#table-info-' + config.id).text('Showing ' + (p.from || 0) + '–' + (p.to || 0) + ' of ' + p.total);
        renderPager($('#table-pages-' + config.id), p);
    };

    self.bindEvents = function() {
        var $c = $(config.container);
        var searchTimer;
        $c.on('input', '.table-search', function() {
            var q = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                self.state.search = q;
                self.state.page = 1;
                self.load();
            }, 350);
        });
        $c.on('keydown', '[data-sort]', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); } });
        $c.on('click', '[data-sort]', function() {
            var key = $(this).data('sort');
            if (self.state.sort === key) {
                self.state.dir = self.state.dir === 'asc' ? 'desc' : 'asc';
            } else {
                self.state.sort = key;
                self.state.dir = 'asc';
            }
            self.state.page = 1;
            self.load();
            $c.find('[data-sort] .sort-indicator').text('');
            $(this).find('.sort-indicator').text(self.state.dir === 'asc' ? ' ↑' : ' ↓');
        });
        $c.on('click', '[data-page]', function() {
            if (!$(this).prop('disabled')) {
                self.state.page = parseInt($(this).data('page'));
                self.load();
            }
        });
    };

    self.refresh = function() { self.state.page = 1; self.load(); };
    self.setFilter = function(key, val) { self.state.filters[key] = val; self.state.page = 1; self.load(); };
};

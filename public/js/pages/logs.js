$(function () {
    var _page = 1;
    var _perPage = 25;
    var _searchTimer = null;

    function getFilters() {
        return {
            q:         $('#log-search').val()      || undefined,
            date_from: $('#log-filter-from').val() || undefined,
            date_to:   $('#log-filter-to').val()   || undefined,
            page:      _page,
            per_page:  _perPage,
        };
    }

    function renderRow(log) {
        var time = log.created_at
            ? new Date(log.created_at).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
            : '—';
        var actor = esc(log.actor || 'System');
        var action = esc(log.action || '');
        var desc   = esc(log.description || '');
        return '<tr class="border-b border-zinc-50 hover:bg-zinc-50">' +
            '<td class="px-4 py-3 text-xs text-zinc-400 whitespace-nowrap">' + time + '</td>' +
            '<td class="px-4 py-3 text-sm text-zinc-700">' + actor + '</td>' +
            '<td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 text-xs font-mono rounded bg-zinc-100 text-zinc-600">' + action + '</span></td>' +
            '<td class="px-4 py-3 text-sm text-zinc-600">' + desc + '</td>' +
            '</tr>';
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.last_page <= 1) {
            $('#log-pagination').addClass('hidden');
            return;
        }
        $('#log-pagination').removeClass('hidden');
        $('#log-page-info').text('Showing ' + pagination.from + '–' + pagination.to + ' of ' + pagination.total);
        renderPager($('#log-page-btns'), pagination);
    }

    function load() {
        var $tbody = $('#log-tbody');
        $tbody.html('<tr><td colspan="4" class="px-4 py-4">' +
            '<div class="space-y-3">' +
            [1,2,3,4,5].map(function() {
                return '<div class="flex gap-4 animate-pulse">' +
                    '<div class="h-4 bg-zinc-100 rounded w-32"></div>' +
                    '<div class="h-4 bg-zinc-100 rounded w-28"></div>' +
                    '<div class="h-4 bg-zinc-100 rounded w-40"></div>' +
                    '<div class="h-4 bg-zinc-100 rounded flex-1"></div>' +
                    '</div>';
            }).join('') + '</div></td></tr>');

        api('/api/logs', {
            method: 'GET',
            data: getFilters(),
            success: function (res) {
                var logs = res.data || [];
                $tbody.empty();
                if (logs.length === 0) {
                    $('#log-empty').removeClass('hidden');
                    $('#logs-page table').addClass('hidden');
                    $('#log-pagination').addClass('hidden');
                } else {
                    $('#log-empty').addClass('hidden');
                    $('#logs-page table').removeClass('hidden');
                    logs.forEach(function (log) { $tbody.append(renderRow(log)); });
                    renderPagination(res.pagination);
                }
            },
            error: function () {
                toast.error('Failed to load logs.');
                $tbody.html('<tr><td colspan="4" class="px-4 py-4 text-sm text-zinc-400 text-center">Error loading logs.</td></tr>');
            },
        });
    }

    $('#log-page-btns').on('click', '[data-page]', function () {
        _page = parseInt($(this).data('page'), 10) || 1;
        load();
    });

    $('#log-search').on('input', function () {
        clearTimeout(_searchTimer);
        _searchTimer = setTimeout(function () { _page = 1; load(); }, 350);
    });

    $('#log-filter-from, #log-filter-to').on('change', function () { _page = 1; load(); });

    $('#log-clear').on('click', function () {
        $('#log-search').val('');
        $('#log-filter-from').val('');
        $('#log-filter-to').val('');
        _page = 1;
        load();
    });

    load();
});

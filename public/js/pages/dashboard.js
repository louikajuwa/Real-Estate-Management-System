$(function () {
    var _charts = {};

    // Static class lookup — building Tailwind class names from data (bg-${x}-50)
    // is unreliable under the Play CDN, so map to full static strings.
    var SUB_BADGE = {
        emerald: 'bg-emerald-50 text-emerald-700',
        indigo:  'bg-indigo-50 text-indigo-700',
        blue:    'bg-blue-50 text-blue-700',
        amber:   'bg-amber-50 text-amber-700',
    };

    function initStatCards(stats) {
        stats = stats || {};
        var props = stats.properties || {};
        var bookings = stats.bookings || {};
        var cards = [
            {
                label: 'PROPERTIES',
                value: props.total ?? 0,
                sub: (props.available ?? 0) + ' available',
                subColor: 'emerald',
            },
            {
                label: 'AGENTS',
                value: stats.agents ?? 0,
                sub: 'active agents',
                subColor: 'indigo',
            },
            {
                label: 'CLIENTS',
                value: stats.clients ?? 0,
                sub: 'total clients',
                subColor: 'blue',
            },
            {
                label: 'BOOKINGS',
                value: bookings.upcoming ?? 0,
                sub: (bookings.total ?? 0) + ' total',
                subColor: 'amber',
            },
        ];
        var html = cards.map(function (c) {
            return '<div class="card p-6">' +
                '<p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-3">' + c.label + '</p>' +
                '<p class="text-3xl font-bold text-zinc-900 mb-2">' + c.value + '</p>' +
                '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full ' + (SUB_BADGE[c.subColor] || 'bg-zinc-100 text-zinc-600') + '">' + c.sub + '</span>' +
                '</div>';
        }).join('');
        $('#stat-cards').html(html);
    }

    function initCharts(charts) {
        // Inquiry Pipeline bar
        $('#chart-inquiry-skeleton').hide();
        if (_charts.inquiry) _charts.inquiry.destroy();
        _charts.inquiry = new Chart(document.getElementById('chart-inquiry'), {
            type: 'bar',
            data: {
                labels: charts.inquiry_pipeline.labels,
                datasets: [{
                    label: 'Inquiries',
                    data: charts.inquiry_pipeline.data,
                    backgroundColor: ['#6366f1','#3b82f6','#f59e0b','#f97316','#71717a'],
                    borderRadius: 4,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#71717a', font: { size: 11 } }, grid: { color: '#f4f4f5' } },
                    x: { ticks: { color: '#71717a', font: { size: 11 } }, grid: { display: false } },
                },
            },
        });

        // Property doughnut
        $('#chart-property-skeleton').hide();
        if (_charts.property) _charts.property.destroy();
        _charts.property = new Chart(document.getElementById('chart-property'), {
            type: 'doughnut',
            data: {
                labels: charts.property_distribution.labels,
                datasets: [{
                    data: charts.property_distribution.data,
                    backgroundColor: ['#10b981','#f59e0b','#f43f5e'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#71717a', font: { size: 11 }, boxWidth: 10, padding: 12 },
                    },
                },
            },
        });

        // Bookings trend line
        $('#chart-bookings-skeleton').hide();
        if (_charts.bookings) _charts.bookings.destroy();
        _charts.bookings = new Chart(document.getElementById('chart-bookings'), {
            type: 'line',
            data: {
                labels: charts.bookings_trend.labels,
                datasets: [{
                    label: 'Bookings',
                    data: charts.bookings_trend.data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: '#6366f1',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#71717a', font: { size: 11 } }, grid: { color: '#f4f4f5' } },
                    x: {
                        ticks: {
                            color: '#71717a',
                            font: { size: 10 },
                            maxTicksLimit: 10,
                            maxRotation: 0,
                        },
                        grid: { display: false },
                    },
                },
            },
        });
    }

    function initActivity(items) {
        if (!items || items.length === 0) {
            $('#activity-feed').empty();
            $('#activity-empty').removeClass('hidden');
            return;
        }
        var initials = function (name) {
            return (name || 'S').split(' ').slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
        };
        var relTime = function (iso) {
            if (!iso) return '';
            var diff = Math.floor((Date.now() - new Date(iso)) / 1000);
            if (diff < 60)   return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        };
        var html = items.map(function (log) {
            return '<div class="flex items-start gap-3">' +
                '<div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-semibold shrink-0">' +
                esc(initials(log.actor)) +
                '</div>' +
                '<div class="flex-1 min-w-0">' +
                '<p class="text-sm text-zinc-700 leading-snug">' + esc(log.description) + '</p>' +
                '<p class="text-xs text-zinc-400 mt-0.5">' + esc(log.actor) + ' · ' + relTime(log.created_at) + '</p>' +
                '</div>' +
                '</div>';
        }).join('');
        $('#activity-feed').html(html);
    }

    function load() {
        api('/api/dashboard/stats', {
            method: 'GET',
            success: function (res) { initStatCards(res.data); },
            error: function () {
                $('#stat-cards').html(
                    ['PROPERTIES','AGENTS','CLIENTS','BOOKINGS'].map(function (l) {
                        return '<div class="card p-6">' +
                            '<p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-3">' + l + '</p>' +
                            '<p class="text-3xl font-bold text-zinc-400">—</p>' +
                            '</div>';
                    }).join('')
                );
            },
        });

        api('/api/dashboard/charts', {
            method: 'GET',
            success: function (res) { initCharts(res.data); },
        });

        api('/api/dashboard/activity', {
            method: 'GET',
            success: function (res) { initActivity(res.data); },
        });
    }

    load();
});

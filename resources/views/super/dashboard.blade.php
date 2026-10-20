@extends('layouts.app')
@section('title', 'Platform Dashboard')
@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900">Platform Dashboard</h1>
            <p class="text-sm text-zinc-500 mt-0.5">Platform-wide overview for all agencies</p>
        </div>
        <a href="{{ route('super.agencies') }}"
           class="btn btn-primary">
            Manage Agencies
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="stat-cards">
        @foreach([
            ['label' => 'Active Agencies',   'id' => 'stat-agencies',   'value' => '—'],
            ['label' => 'Total Properties',  'id' => 'stat-properties', 'value' => '—'],
            ['label' => 'Platform Clients',  'id' => 'stat-clients',    'value' => '—'],
            ['label' => 'Inquiries Today',   'id' => 'stat-inquiries',  'value' => '—'],
        ] as $card)
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-2">{{ $card['label'] }}</p>
            <p class="text-3xl font-bold text-zinc-900" id="{{ $card['id'] }}">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Agencies table --}}
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-100">
            <h2 class="text-sm font-semibold text-zinc-900">Agencies</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 bg-zinc-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Agency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Agents</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Properties</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody id="agencies-tbody">
                    <tr id="agencies-skeleton">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-400">Loading…</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="agencies-pagination" class="px-6 py-4 border-t border-zinc-100 hidden"></div>
    </div>

</div>
@endsection
@push('scripts')
<script>
$(function() {
    // Load platform stats
    $.ajax({
        url: '/api/super/stats',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function(res) {
        if (res.success && res.data) {
            $('#stat-agencies').text(res.data.active_agencies ?? '—');
            $('#stat-properties').text(res.data.total_properties ?? '—');
            $('#stat-clients').text(res.data.total_clients ?? '—');
            $('#stat-inquiries').text(res.data.inquiries_today ?? '—');
        }
    }).fail(function() {
        ['stat-agencies','stat-properties','stat-clients','stat-inquiries'].forEach(function(id) {
            $('#' + id).text('—');
        });
    });

    // Load agencies table
    loadAgencies(1);

    function loadAgencies(page) {
        $.ajax({
            url: '/api/super/agencies?page=' + page,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(res) {
            if (!res.success || !res.data) { return; }
            var rows = '';
            res.data.forEach(function(a) {
                var statusBadge = a.is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-danger">Inactive</span>';
                var planBadge = '<span class="badge badge-neutral">' + escHtml(a.plan_type) + '</span>';
                rows += '<tr class="border-b border-zinc-50 hover:bg-zinc-50 transition-colors">'
                    + '<td class="px-6 py-4"><span class="font-medium text-zinc-900">' + escHtml(a.name) + '</span><br><span class="text-xs text-zinc-400">' + escHtml(a.city || '') + '</span></td>'
                    + '<td class="px-6 py-4">' + planBadge + '</td>'
                    + '<td class="px-6 py-4 text-zinc-700">' + (a.agents_count ?? 0) + '</td>'
                    + '<td class="px-6 py-4 text-zinc-700">' + (a.properties_count ?? 0) + '</td>'
                    + '<td class="px-6 py-4">' + statusBadge + '</td>'
                    + '<td class="px-6 py-4"><a href="{{ route("super.agencies") }}" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">Manage →</a></td>'
                    + '</tr>';
            });
            $('#agencies-tbody').html(rows || '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-400">No agencies yet.</td></tr>');
        }).fail(function() {
            $('#agencies-tbody').html('<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-rose-500">Failed to load agencies.</td></tr>');
        });
    }
});
</script>
@endpush

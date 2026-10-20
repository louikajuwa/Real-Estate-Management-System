@extends('layouts.app')
@section('title', 'Agencies')
@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900">Agencies</h1>
            <p class="text-sm text-zinc-500 mt-0.5">Manage all agencies on the platform</p>
        </div>
        <button type="button" id="create-agency-btn"
                class="btn btn-primary">
            Create Agency
        </button>
    </div>

    {{-- Search + filter --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[12rem] max-w-sm">
            <input type="search" id="agency-search" placeholder="Search by name, email, city…" aria-label="Search agencies"
                   class="input w-full pl-9 pr-3">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-zinc-400 pointer-events-none" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <select id="agency-status-filter" class="input input-sm" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 bg-zinc-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Agency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Agents</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Properties</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody id="agencies-tbody">
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-zinc-400">Loading…</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="agencies-pagination" class="px-6 py-4 border-t border-zinc-100 flex flex-wrap items-center gap-1 hidden" role="navigation" aria-label="Pagination"></div>
    </div>
</div>

{{-- Create Agency Modal --}}
<div id="create-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="create-modal-title" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h2 id="create-modal-title" class="text-base font-semibold text-zinc-900">Create New Agency</h2>
            <button type="button" id="close-modal" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="create-agency-form" class="space-y-4" autocomplete="off">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="f-name" class="label">Agency Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="f-name" required
                           class="input w-full">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="err-name"></p>
                </div>
                <div>
                    <label for="f-admin-name" class="label">Admin Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="admin_name" id="f-admin-name" required
                           class="input w-full">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="err-admin_name"></p>
                </div>
                <div>
                    <label for="f-admin-email" class="label">Admin Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="admin_email" id="f-admin-email" required
                           class="input w-full">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="err-admin_email"></p>
                </div>
                <div>
                    <label for="f-city" class="label">City</label>
                    <input type="text" name="city" id="f-city"
                           class="input w-full">
                </div>
                <div>
                    <label for="f-plan" class="label">Plan</label>
                    <select name="plan_type" id="f-plan"
                            class="input w-full">
                        <option value="trial">Trial</option>
                        <option value="basic">Basic</option>
                        <option value="pro">Pro</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="f-tagline" class="label">Tagline</label>
                    <input type="text" name="tagline" id="f-tagline"
                           class="input w-full">
                </div>
            </div>

            <p id="form-error" class="text-xs text-rose-600 hidden"></p>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="cancel-btn" data-modal-close
                        class="btn btn-secondary">
                    Cancel
                </button>
                <button type="submit" id="submit-btn"
                        class="btn btn-primary">
                    <span id="submit-text">Save</span>
                    <svg id="submit-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </button>
            </div>
        </form>

        {{-- Success panel --}}
        <div id="create-success" class="hidden">
            <div class="text-center py-4">
                <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-zinc-900 mb-1">Agency Created!</h3>
                <p class="text-xs text-zinc-500 mb-4">Share these credentials with the agency admin:</p>
                <div class="bg-zinc-50 rounded-lg p-4 text-left text-xs space-y-1.5 mb-4">
                    <div><span class="text-zinc-500">Email:</span> <span class="font-medium text-zinc-900" id="cred-email"></span></div>
                    <div><span class="text-zinc-500">Password:</span> <span class="font-medium text-zinc-900" id="cred-password"></span></div>
                </div>
                <button type="button" id="done-btn"
                        class="btn btn-primary">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Agency Detail Modal --}}
<div id="detail-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="d-name" class="bg-white rounded-xl shadow-xl w-full max-w-2xl ">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100 sticky top-0 bg-white">
            <div>
                <h2 class="text-base font-semibold text-zinc-900" id="d-name">Agency</h2>
                <p class="text-xs text-zinc-500" id="d-sub"></p>
            </div>
            <button type="button" id="detail-close" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-6">
            {{-- Stat row --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-zinc-50 rounded-lg p-4 text-center"><p class="text-2xl font-bold text-zinc-900" id="d-agents">0</p><p class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Agents</p></div>
                <div class="bg-zinc-50 rounded-lg p-4 text-center"><p class="text-2xl font-bold text-zinc-900" id="d-properties">0</p><p class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Properties</p></div>
                <div class="bg-zinc-50 rounded-lg p-4 text-center"><p class="text-2xl font-bold text-zinc-900" id="d-customers">0</p><p class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Customers</p></div>
            </div>

            {{-- Info --}}
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Information</h3>
                <dl class="text-sm grid grid-cols-2 gap-x-6 gap-y-2" id="d-info"></dl>
            </div>

            {{-- Agents --}}
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Agents</h3>
                <div id="d-agents-list" class="space-y-1.5"></div>
            </div>

            {{-- Listings --}}
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Listings</h3>
                <div id="d-properties-list" class="space-y-1.5"></div>
            </div>

            {{-- Customers --}}
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Customers</h3>
                <div id="d-customers-list" class="space-y-1.5"></div>
            </div>

            {{-- Reset-password result --}}
            <div id="d-reset-creds" class="hidden bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm">
                <p class="font-medium text-amber-800 mb-1">New admin password generated</p>
                <p class="text-amber-700 text-xs">Share with the agency admin — shown only once:</p>
                <div class="mt-2 text-xs"><span class="text-zinc-500">Email:</span> <span class="font-medium" id="d-reset-email"></span></div>
                <div class="text-xs"><span class="text-zinc-500">Password:</span> <span class="font-medium" id="d-reset-password"></span></div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-zinc-100">
                <button type="button" id="d-reset-btn" class="btn btn-sm bg-amber-50 text-amber-700 hover:bg-amber-100">Reset Admin Password</button>
                <button type="button" id="d-edit-btn" class="btn btn-primary btn-sm">Edit Agency</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Agency Modal --}}
<div id="edit-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="edit-modal-title" class="bg-white rounded-xl shadow-xl w-full max-w-lg ">
        <div class="flex items-center justify-between mb-5">
            <h2 id="edit-modal-title" class="text-base font-semibold text-zinc-900">Edit Agency</h2>
            <button type="button" id="edit-close" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-agency-form" class="space-y-4" autocomplete="off">
            <input type="hidden" id="e-slug">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="e-name" class="label">Agency Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="e-name" class="input w-full">
                </div>
                <div><label for="e-email" class="label">Email</label><input type="email" id="e-email" class="input w-full"></div>
                <div><label for="e-phone" class="label">Phone</label><input type="text" id="e-phone" class="input w-full"></div>
                <div><label for="e-city" class="label">City</label><input type="text" id="e-city" class="input w-full"></div>
                <div><label for="e-country" class="label">Country</label><input type="text" id="e-country" class="input w-full"></div>
                <div><label for="e-website" class="label">Website</label><input type="text" id="e-website" class="input w-full"></div>
                <div>
                    <label for="e-plan" class="label">Plan</label>
                    <select id="e-plan" class="input w-full">
                        <option value="trial">Trial</option><option value="basic">Basic</option><option value="pro">Pro</option>
                    </select>
                </div>
                <div class="sm:col-span-2"><label for="e-tagline" class="label">Tagline</label><input type="text" id="e-tagline" class="input w-full"></div>
                <div class="sm:col-span-2"><label for="e-address" class="label">Address</label><textarea id="e-address" rows="2" class="input w-full resize-none"></textarea></div>
            </div>
            <p id="edit-error" class="text-xs text-rose-600 hidden"></p>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="edit-cancel" data-modal-close class="btn btn-secondary">Cancel</button>
                <button type="submit" id="edit-submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
$(function() {
    var currentPage = 1;

    loadAgencies(1);

    function loadAgencies(page) {
        currentPage = page;
        $('#agencies-tbody').html('<tr><td colspan="7" class="px-6 py-8 text-center text-sm text-zinc-400">Loading…</td></tr>');

        var params = { page: page };
        var q = $('#agency-search').val();
        var status = $('#agency-status-filter').val();
        if (q) params.q = q;
        if (status) params.status = status;

        $.ajax({
            url: '/api/super/agencies',
            data: params,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(res) {
            if (!res.success) { return showTableError(); }

            var agencies = res.data;
            if (!agencies || agencies.length === 0) {
                $('#agencies-tbody').html('<tr><td colspan="7" class="px-6 py-12 text-center"><div class="flex flex-col items-center gap-2"><svg class="w-10 h-10 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg><p class="text-sm text-zinc-500 font-medium">No agencies yet</p><p class="text-xs text-zinc-400">Create the first agency above</p></div></td></tr>');
                return;
            }

            var rows = agencies.map(function(a) {
                var statusBadge = a.is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-danger">Inactive</span>';
                var toggleLabel = a.is_active ? 'Deactivate' : 'Activate';
                var toggleClass = a.is_active ? 'text-rose-600 hover:text-rose-700' : 'text-emerald-600 hover:text-emerald-700';
                return '<tr class="border-b border-zinc-50 hover:bg-zinc-50 transition-colors">'
                    + '<td class="px-6 py-4"><span class="font-medium text-zinc-900">' + escHtml(a.name) + '</span></td>'
                    + '<td class="px-6 py-4 text-zinc-500 text-xs">' + escHtml(a.email || '') + '</td>'
                    + '<td class="px-6 py-4"><span class="badge badge-neutral">' + escHtml(a.plan_type) + '</span></td>'
                    + '<td class="px-6 py-4 text-zinc-700">' + (a.agents_count ?? 0) + '</td>'
                    + '<td class="px-6 py-4 text-zinc-700">' + (a.properties_count ?? 0) + '</td>'
                    + '<td class="px-6 py-4">' + statusBadge + '</td>'
                    + '<td class="px-6 py-4 whitespace-nowrap space-x-3">'
                        + '<button class="view-agency text-xs font-medium text-indigo-600 hover:text-indigo-700" data-slug="' + escHtml(a.slug) + '">View</button>'
                        + '<button class="toggle-status text-xs font-medium ' + toggleClass + '" data-slug="' + escHtml(a.slug) + '" data-active="' + a.is_active + '">' + toggleLabel + '</button>'
                        + '<button class="delete-agency text-xs font-medium text-rose-600 hover:text-rose-700" data-slug="' + escHtml(a.slug) + '" data-name="' + escHtml(a.name) + '">Delete</button>'
                    + '</td>'
                    + '</tr>';
            }).join('');
            $('#agencies-tbody').html(rows);

            // Pagination
            if (res.pagination && res.pagination.last_page > 1) {
                renderPager($('#agencies-pagination'), res.pagination);
                $('#agencies-pagination').removeClass('hidden');
            } else {
                $('#agencies-pagination').addClass('hidden');
            }
        }).fail(showTableError);
    }

    function showTableError() {
        $('#agencies-tbody').html('<tr><td colspan="7" class="px-6 py-8 text-center text-sm text-rose-500">Failed to load agencies.</td></tr>');
    }

    // Pagination
    $(document).on('click', '#agencies-pagination [data-page]', function() { loadAgencies($(this).data('page')); });

    // View agency detail
    $(document).on('click', '.view-agency', function() {
        var slug = $(this).data('slug');
        openDetail(slug);
    });

    var detailAgency = null;

    function openDetail(slug) {
        Modal.open('detail-modal');
        $('#d-reset-creds').addClass('hidden');
        $('#d-name').text('Loading…');
        $('#d-sub').text('');
        $('#d-info').empty();
        $('#d-agents-list').html('<p class="text-xs text-zinc-400">Loading…</p>');
        $('#d-properties-list').html('<p class="text-xs text-zinc-400">Loading…</p>');
        $('#d-customers-list').html('<p class="text-xs text-zinc-400">Loading…</p>');

        $.ajax({
            url: '/api/super/agencies/' + slug,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(res) {
            if (!res.success || !res.data) { $('#d-name').text('Failed to load'); return; }
            var a = res.data;
            detailAgency = a;
            $('#d-name').text(a.name || '—');
            $('#d-sub').text((a.slug || '') + ' · ' + (a.is_active ? 'Active' : 'Inactive') + ' · ' + (a.plan_type || ''));
            $('#d-agents').text(a.agents_count ?? 0);
            $('#d-properties').text(a.properties_count ?? 0);
            $('#d-customers').text(a.clients_count ?? 0);

            var info = [
                ['Email', a.email], ['Phone', a.phone], ['City', a.city],
                ['Country', a.country], ['Website', a.website], ['Tagline', a.tagline],
            ];
            $('#d-info').html(info.map(function(row) {
                return '<div><dt class="text-zinc-400 text-xs">' + row[0] + '</dt><dd class="text-zinc-800">' + escHtml(row[1] || '—') + '</dd></div>';
            }).join(''));

            var agents = a.agents || [];
            $('#d-agents-list').html(agents.length
                ? agents.map(function(ag) {
                    var nm = ag.user ? ag.user.name : ('Agent #' + ag.id);
                    var em = ag.user ? ag.user.email : '';
                    return '<div class="flex justify-between text-sm border border-zinc-100 rounded-lg px-3 py-2"><span class="text-zinc-800">' + escHtml(nm) + '</span><span class="text-zinc-400 text-xs">' + escHtml(em) + '</span></div>';
                  }).join('')
                : '<p class="text-xs text-zinc-400">No agents.</p>');

            var statusMap = { available: 'bg-emerald-50 text-emerald-700', reserved: 'bg-amber-50 text-amber-700', sold: 'bg-rose-50 text-rose-700' };
            var props = a.properties || [];
            $('#d-properties-list').html(props.length
                ? props.map(function(p) {
                    var badge = '<span class="px-2 py-0.5 text-xs font-medium rounded-full ' + (statusMap[p.status] || 'bg-zinc-100 text-zinc-600') + '">' + escHtml(p.status) + '</span>';
                    var price = 'AED ' + Number(p.price || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
                    return '<div class="flex justify-between items-center text-sm border border-zinc-100 rounded-lg px-3 py-2">'
                        + '<div class="min-w-0"><span class="text-zinc-800">' + escHtml(p.title) + '</span>'
                        + ' <span class="text-zinc-400 text-xs">' + escHtml((p.city || '') + ' · ' + (p.type || '')) + (p.agent ? ' · ' + escHtml(p.agent) : '') + '</span></div>'
                        + '<div class="flex items-center gap-2 shrink-0"><span class="text-indigo-600 font-medium text-xs">' + price + '</span>' + badge + '</div>'
                        + '</div>';
                  }).join('')
                : '<p class="text-xs text-zinc-400">No listings.</p>');

            var clients = a.clients || [];
            $('#d-customers-list').html(clients.length
                ? clients.map(function(c) {
                    return '<div class="flex justify-between items-center text-sm border border-zinc-100 rounded-lg px-3 py-2">'
                        + '<div><span class="text-zinc-800">' + escHtml(c.name) + '</span> <span class="text-zinc-400 text-xs">' + escHtml(c.email || '') + '</span></div>'
                        + '<span class="text-xs text-zinc-400">' + (c.inquiries_count ?? 0) + ' inq · ' + (c.bookings_count ?? 0) + ' bk</span>'
                        + '</div>';
                  }).join('')
                : '<p class="text-xs text-zinc-400">No customers yet.</p>');
        }).fail(function() { $('#d-name').text('Failed to load'); });
    }


    // Search + status filter (debounced)
    var searchTimer;
    $('#agency-search').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { loadAgencies(1); }, 300);
    });
    $('#agency-status-filter').on('change', function() { loadAgencies(1); });

    // Reset admin password (from the detail modal)
    $('#d-reset-btn').on('click', function() {
        if (!detailAgency) return;
        if (!confirm('Generate a new password for ' + detailAgency.name + "'s admin? The current password stops working immediately.")) return;
        var $btn = $(this).prop('disabled', true).text('Resetting…');
        $.ajax({
            url: '/api/super/agencies/' + detailAgency.slug + '/reset-admin-password',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(res) {
            if (res.success && res.data) {
                $('#d-reset-email').text(res.data.admin_email);
                $('#d-reset-password').text(res.data.admin_password);
                $('#d-reset-creds').removeClass('hidden');
                if (typeof toast !== 'undefined') toast.success('Admin password reset.');
            }
        }).fail(function(xhr) {
            var res = xhr.responseJSON || {};
            if (typeof toast !== 'undefined') toast.error(res.message || 'Failed to reset password.');
        }).always(function() { $btn.prop('disabled', false).text('Reset Admin Password'); });
    });

    // Edit agency (prefill from the loaded detail)
    $('#d-edit-btn').on('click', function() {
        if (!detailAgency) return;
        var a = detailAgency;
        $('#e-slug').val(a.slug);
        $('#e-name').val(a.name || '');
        $('#e-email').val(a.email || '');
        $('#e-phone').val(a.phone || '');
        $('#e-city').val(a.city || '');
        $('#e-country').val(a.country || '');
        $('#e-website').val(a.website || '');
        $('#e-plan').val(a.plan_type || 'trial');
        $('#e-tagline').val(a.tagline || '');
        $('#e-address').val(a.address || '');
        $('#edit-error').addClass('hidden');
        Modal.close();
        Modal.open('edit-modal');
    });


    $('#edit-agency-form').on('submit', function(e) {
        e.preventDefault();
        var slug = $('#e-slug').val();
        $('#edit-error').addClass('hidden');
        var $editBtn = $('#edit-submit').prop('disabled', true).attr('aria-busy', 'true');
        $.ajax({
            url: '/api/super/agencies/' + slug,
            method: 'PUT',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({
                name: $('#e-name').val(), email: $('#e-email').val(), phone: $('#e-phone').val(),
                city: $('#e-city').val(), country: $('#e-country').val(), website: $('#e-website').val(),
                plan_type: $('#e-plan').val(), tagline: $('#e-tagline').val(), address: $('#e-address').val(),
            })
        }).done(function(res) {
            if (res.success) {
                Modal.close();
                if (typeof toast !== 'undefined') toast.success('Agency updated.');
                loadAgencies(currentPage);
            }
        }).fail(function(xhr) {
            var res = xhr.responseJSON || {};
            $('#edit-error').text(res.message || 'Failed to update agency.').removeClass('hidden');
        }).always(function() { $editBtn.prop('disabled', false).removeAttr('aria-busy'); });
    });

    // Delete agency
    $(document).on('click', '.delete-agency', function() {
        var slug = $(this).data('slug');
        var name = $(this).data('name');
        if (!confirm('Delete "' + name + '"?\n\nThis permanently removes the agency, its staff accounts, agents, and property listings. Customers are detached but keep their accounts. This cannot be undone.')) return;
        $.ajax({
            url: '/api/super/agencies/' + slug,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(res) {
            if (typeof toast !== 'undefined') toast.success((res && res.message) || 'Agency deleted.');
            loadAgencies(1);
        }).fail(function(xhr) {
            var res = xhr.responseJSON || {};
            if (typeof toast !== 'undefined') toast.error(res.message || 'Failed to delete agency.');
        });
    });

    // Toggle status
    $(document).on('click', '.toggle-status', function() {
        var slug = $(this).data('slug');
        var $btn = $(this);
        $btn.prop('disabled', true).text('…');
        $.ajax({
            url: '/api/super/agencies/' + slug + '/status',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function() { loadAgencies(currentPage); })
          .fail(function() { loadAgencies(currentPage); });
    });

    // Open/close modal
    $('#create-agency-btn').on('click', function() { openModal(); });

    function openModal() {
        $('#create-agency-form').show().trigger('reset');
        $('#create-success').hide();
        $('#form-error').addClass('hidden');
        $('[id^="err-"]').addClass('hidden').text('');
        Modal.open('create-modal');
    }

    function closeModal() {
        Modal.close();
    }

    // Form submit
    $('#create-agency-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#submit-btn');
        $btn.prop('disabled', true).attr('aria-busy', 'true');
        $('#submit-text').text('Saving…');
        $('#submit-spinner').removeClass('hidden');
        $('#form-error').addClass('hidden');
        $('[id^="err-"]').addClass('hidden').text('');

        $.ajax({
            url: '/api/super/agencies',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({
                name:        $('#f-name').val(),
                admin_name:  $('#f-admin-name').val(),
                admin_email: $('#f-admin-email').val(),
                city:        $('#f-city').val(),
                plan_type:   $('#f-plan').val(),
                tagline:     $('#f-tagline').val(),
            })
        }).done(function(res) {
            if (res.success && res.data) {
                $('#cred-email').text(res.data.admin_email);
                $('#cred-password').text(res.data.admin_password);
                $('#create-agency-form').hide();
                $('#create-success').show();
                $('#done-btn').trigger('focus');
                loadAgencies(currentPage);
            }
        }).fail(function(xhr) {
            var res = xhr.responseJSON || {};
            if (xhr.status === 422 && res.errors) {
                Object.keys(res.errors).forEach(function(field) {
                    var $err = $('#err-' + field);
                    if ($err.length) $err.text(Array.isArray(res.errors[field]) ? res.errors[field][0] : res.errors[field]).removeClass('hidden');
                });
                $('#form-error').text('Please fix the errors above.').removeClass('hidden');
            } else {
                $('#form-error').text(res.message || 'Failed to create agency.').removeClass('hidden');
            }
        }).always(function() {
            $btn.prop('disabled', false).removeAttr('aria-busy');
            $('#submit-text').text('Save');
            $('#submit-spinner').addClass('hidden');
        });
    });

    $('#done-btn').on('click', closeModal);
});
</script>
@endpush

$(function () {
    var isAdmin = window.__USER__ && window.__USER__.roles && window.__USER__.roles.indexOf('admin') !== -1;
    var clientTable;
    var typeIcons = { call: '📞', email: '✉️', meeting: '🤝', note: '📝' };

    function initTable() {
        clientTable = new Table({
            id: 'clients',
            container: '#client-table-container',
            endpoint: '/api/clients',
            defaultSort: 'created_at',
            defaultDir: 'desc',
            columns: [
                {
                    key: 'name', label: 'Name', sortable: true,
                    render: function (r) {
                        return '<div><p class="font-medium text-zinc-900">' + esc(r.name) + '</p>' + (r.email ? '<p class="text-xs text-zinc-400">' + esc(r.email) + '</p>' : '') + '</div>';
                    }
                },
                { key: 'phone', label: 'Phone', sortable: false, render: function(r) { return r.phone ? esc(r.phone) : '<span class="text-zinc-300">—</span>'; } },
                {
                    key: 'assigned_agent', label: 'Agent', sortable: false,
                    render: function (r) {
                        var name = r.assigned_agent && r.assigned_agent.user ? r.assigned_agent.user.name : null;
                        return name ? esc(name) : '<span class="text-zinc-300">—</span>';
                    }
                },
                { key: 'inquiries_count', label: 'Inquiries', sortable: false, render: function(r) { return '<span class="text-sm">' + (r.inquiries_count || 0) + '</span>'; } },
                { key: 'bookings_count', label: 'Bookings', sortable: false, render: function(r) { return '<span class="text-sm">' + (r.bookings_count || 0) + '</span>'; } },
            ],
            actions: function (r) {
                return '<div class="flex items-center justify-end gap-1">' +
                    '<button type="button" class="btn-timeline btn-icon hover:text-indigo-600" data-id="' + r.id + '" data-name="' + esc(r.name) + '" title="View interactions" aria-label="View interactions for ' + esc(r.name) + '"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>' +
                    '<button type="button" class="btn-edit-client btn-icon hover:text-indigo-600" data-id="' + r.id + '" title="Edit" aria-label="Edit ' + esc(r.name) + '"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>' +
                    '<button type="button" class="btn-del-client btn-icon hover:text-rose-600" data-id="' + r.id + '" data-name="' + esc(r.name) + '" title="Delete" aria-label="Delete ' + esc(r.name) + '"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>' +
                    '</div>';
            },
            headerActions: '<button type="button" id="btn-add-client" class="btn btn-primary btn-sm gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg> Add Client</button>',
        });
        clientTable.init();
    }

    function loadAgentsDropdown(selectedId) {
        api.get('/api/agents').done(function (res) {
            var $sel = $('#cf-agent').empty().append('<option value="">None</option>');
            (res.data || []).forEach(function (a) {
                var name = a.user ? a.user.name : 'Agent #' + a.id;
                $sel.append('<option value="' + a.id + '"' + (a.id == selectedId ? ' selected' : '') + '>' + esc(name) + '</option>');
            });
            // Populate filter too
            var $filter = $('#filter-agent');
            if ($filter.length && $filter.children().length <= 1) {
                (res.data || []).forEach(function (a) {
                    var name = a.user ? a.user.name : 'Agent #' + a.id;
                    $filter.append('<option value="' + a.id + '">' + esc(name) + '</option>');
                });
            }
        });
    }

    function openAddModal() {
        $('#client-id').val('');
        $('#client-form')[0].reset();
        $('#client-modal-title').text('Add Client');
        clearClientErrors();
        if (isAdmin) loadAgentsDropdown();
        Modal.open('client-modal');
    }

    function openEditModal(id) {
        api.get('/api/clients/' + id).done(function (res) {
            var c = res.data;
            $('#client-id').val(c.id);
            $('#client-modal-title').text('Edit Client');
            $('#cf-name').val(c.name);
            $('#cf-email').val(c.email || '');
            $('#cf-phone').val(c.phone || '');
            $('#cf-address').val(c.address || '');
            $('#cf-notes').val(c.notes || '');
            if (isAdmin) loadAgentsDropdown(c.assigned_agent_id);
            clearClientErrors();
            Modal.open('client-modal');
        });
    }

    function clearClientErrors() {
        $('[id^="cerr-"]').addClass('hidden').text('');
    }

    $('#client-form').on('submit', function (e) {
        e.preventDefault();
        var id = $('#client-id').val();
        var $btn = $('#client-save-btn');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).attr('aria-busy', 'true');
        $('#client-save-text').text('Saving…');
        $('#client-save-spin').removeClass('hidden');
        clearClientErrors();

        var data = {
            name: $('#cf-name').val(),
            email: $('#cf-email').val() || null,
            phone: $('#cf-phone').val() || null,
            address: $('#cf-address').val() || null,
            notes: $('#cf-notes').val() || null,
        };
        // Always send the key for admins so selecting "None" can un-assign the agent.
        if (isAdmin) data.assigned_agent_id = $('#cf-agent').val() ? parseInt($('#cf-agent').val()) : null;

        var req = id ? api.put('/api/clients/' + id, data) : api.post('/api/clients', data);
        req.done(function () {
            Modal.close(); toast.success(id ? 'Client updated.' : 'Client created.');
            clientTable.refresh();
        }).fail(function (xhr) {
            var r = xhr.responseJSON || {};
            if (!r.errors && xhr.status !== 0 && xhr.status !== 401 && xhr.status !== 419 && xhr.status !== 422 && xhr.status < 500) toast.error(r.message || 'Could not save the client.');
            if (r.errors) Object.keys(r.errors).forEach(function (f) { $('#cerr-' + f).text(Array.isArray(r.errors[f]) ? r.errors[f][0] : r.errors[f]).removeClass('hidden'); });
        }).always(function () {
            $btn.prop('disabled', false).removeAttr('aria-busy');
            $('#client-save-text').text('Save');
            $('#client-save-spin').addClass('hidden');
        });
    });

    // Timeline
    function setNowInInteractionForm() {
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $('#if-occurred-at').val(now.toISOString().slice(0, 16));
    }

    function openTimeline(id, name) {
        $('#interaction-client-id').val(id);
        $('#timeline-title').text(name + ' — History');
        $('#interaction-timeline').html('<div class="animate-pulse space-y-3">' + Array(3).fill('<div class="flex gap-3"><div class="w-2 h-2 mt-1.5 rounded-full bg-zinc-200"></div><div class="flex-1 h-3 bg-zinc-100 rounded"></div></div>').join('') + '</div>');
        setNowInInteractionForm();
        Modal.open('timeline-modal');
        loadTimeline(id);
    }

    function loadTimeline(clientId) {
        api.get('/api/clients/' + clientId + '/interactions').done(function (res) {
            renderTimeline(res.data || []);
        }).fail(function () {
            $('#interaction-timeline').html('<p class="text-sm text-zinc-400 text-center py-4">Failed to load timeline.</p>');
        });
    }

    function renderTimeline(interactions) {
        var $t = $('#interaction-timeline');
        if (!interactions.length) {
            $t.html('<div class="flex flex-col items-center py-8 gap-2"><svg class="w-8 h-8 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><p class="text-sm font-semibold text-zinc-700">No interactions yet</p><p class="text-xs text-zinc-400">Log the first interaction above</p></div>');
            return;
        }
        var typeColor = { call: 'bg-indigo-100 text-indigo-700', email: 'bg-sky-100 text-sky-700', meeting: 'bg-emerald-100 text-emerald-700', note: 'bg-zinc-100 text-zinc-600' };
        var html = interactions.map(function (i) {
            var typeClass = typeColor[i.type] || 'bg-zinc-100 text-zinc-600';
            var agentName = i.agent && i.agent.user ? i.agent.user.name : null;
            var date = i.occurred_at ? new Date(i.occurred_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
            return '<div class="flex gap-3">' +
                '<div class="flex flex-col items-center"><div class="w-2 h-2 rounded-full bg-zinc-300 mt-1.5 shrink-0"></div><div class="w-px flex-1 bg-zinc-100 mt-1"></div></div>' +
                '<div class="pb-4 flex-1 min-w-0">' +
                '<div class="flex items-center gap-2 mb-1">' +
                '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium ' + typeClass + '">' + esc(i.type) + '</span>' +
                '<span class="text-xs text-zinc-400">' + esc(date) + '</span>' +
                (agentName ? '<span class="text-xs text-zinc-400 ml-auto">' + esc(agentName) + '</span>' : '') +
                '</div>' +
                '<p class="text-sm text-zinc-700 leading-snug">' + esc(i.notes || '') + '</p>' +
                '</div></div>';
        }).join('');
        $t.html(html);
    }

    $('#interaction-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#interaction-save-btn');
        if ($btn.prop('disabled')) return;
        var clientId = $('#interaction-client-id').val();
        var occurred = $('#if-occurred-at').val();
        var payload = { type: $('#if-type').val(), notes: $('#if-notes').val() };
        // Omit occurred_at when empty so the server default applies (was: ":00" appended to "").
        // datetime-local is the viewer's local time; the server stores UTC.
        if (occurred) payload.occurred_at = new Date(occurred).toISOString();
        busy($btn, api.post('/api/clients/' + clientId + '/interactions', payload)).done(function () {
            toast.success('Interaction logged.');
            $('#interaction-form')[0].reset();
            setNowInInteractionForm();
            loadTimeline(clientId);
        }).fail(function (xhr) {
            if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419 || xhr.status === 422 || xhr.status >= 500) return;
            toast.error((xhr.responseJSON || {}).message || 'Could not log the interaction.');
        });
    });

    $(document).on('click', '#btn-add-client', openAddModal);
    $(document).on('click', '.btn-edit-client', function () { openEditModal($(this).data('id')); });
    $(document).on('click', '.btn-timeline', function () { openTimeline($(this).data('id'), $(this).data('name')); });
    $(document).on('click', '.btn-del-client', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        if (!confirm('Delete client "' + name + '"?')) return;
        api.delete('/api/clients/' + id).done(function () {
            toast.success('Client deleted.'); clientTable.refresh();
        }).fail(function (xhr) {
            toast.error((xhr.responseJSON || {}).message || 'Delete failed.');
        });
    });

    // Agent filter (admin only)
    $('#filter-agent').on('change', function () {
        if (clientTable) { clientTable.setFilter('agent_id', $(this).val()); }
    });

    if (isAdmin) loadAgentsDropdown();
    initTable();
});

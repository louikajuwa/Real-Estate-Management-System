$(function () {
    var isAdmin = window.__USER__ && window.__USER__.roles && window.__USER__.roles.indexOf('admin') !== -1;
    var agentTable;

    function initTable() {
        agentTable = new Table({
            id: 'agents',
            container: '#agent-table-container',
            endpoint: '/api/agents',
            defaultSort: 'created_at',
            defaultDir: 'desc',
            columns: [
                {
                    key: 'user', label: 'Name', sortable: false,
                    render: function (r) {
                        var name = r.user ? r.user.name : '—';
                        var email = r.user ? r.user.email : '';
                        return '<div><p class="font-medium text-zinc-900">' + esc(name) + '</p><p class="text-xs text-zinc-400">' + esc(email) + '</p></div>';
                    }
                },
                { key: 'phone', label: 'Phone', sortable: false, render: function(r) { return r.phone ? esc(r.phone) : '<span class="text-zinc-300">—</span>'; } },
                { key: 'license_no', label: 'License', sortable: false, render: function(r) { return r.license_no ? esc(r.license_no) : '<span class="text-zinc-300">—</span>'; } },
                { key: 'properties_count', label: 'Properties', sortable: false, render: function(r) { return '<span class="text-sm text-zinc-700">' + (r.properties_count || 0) + '</span>'; } },
                { key: 'clients_count', label: 'Clients', sortable: false, render: function(r) { return '<span class="text-sm text-zinc-700">' + (r.clients_count || 0) + '</span>'; } },
            ],
            actions: isAdmin ? function (r) {
                return '<div class="flex items-center justify-end gap-1">' +
                    '<button type="button" class="btn-edit-agent btn-icon hover:text-indigo-600" data-id="' + r.id + '" title="Edit" aria-label="Edit ' + esc(r.user ? r.user.name : 'agent') + '"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>' +
                    '<button type="button" class="btn-del-agent btn-icon hover:text-rose-600" data-id="' + r.id + '" data-name="' + (r.user ? esc(r.user.name) : '') + '" title="Delete" aria-label="Delete ' + esc(r.user ? r.user.name : 'agent') + '"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>' +
                    '</div>';
            } : null,
            headerActions: isAdmin ? '<button type="button" id="btn-add-agent" class="btn btn-primary btn-sm gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg> Add Agent</button>' : '',
        });
        agentTable.init();
    }

    function openAddModal() {
        $('#agent-id').val('');
        $('#agent-form')[0].reset();
        $('#agent-modal-title').text('Add Agent');
        $('#af-password').attr('required', true);
        $('#pwd-hint').text('(required)');
        clearErrors();
        Modal.open('agent-modal');
    }

    function openEditModal(id) {
        api.get('/api/agents/' + id).done(function (res) {
            var a = res.data;
            $('#agent-id').val(a.id);
            $('#agent-modal-title').text('Edit Agent');
            $('#af-name').val(a.user ? a.user.name : '');
            $('#af-email').val(a.user ? a.user.email : '');
            $('#af-phone').val(a.phone || '');
            $('#af-license').val(a.license_no || '');
            $('#af-bio').val(a.bio || '');
            $('#af-password').val('').removeAttr('required');
            $('#pwd-hint').text('(leave blank to keep current)');
            clearErrors();
            Modal.open('agent-modal');
        });
    }

    function clearErrors() {
        $('[id^="aerr-"]').addClass('hidden').text('');
    }

    $('#agent-form').on('submit', function (e) {
        e.preventDefault();
        var id = $('#agent-id').val();
        var $btn = $('#agent-save-btn');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).attr('aria-busy', 'true');
        $('#agent-save-text').text('Saving…');
        $('#agent-save-spin').removeClass('hidden');
        clearErrors();

        var data = { name: $('#af-name').val(), email: $('#af-email').val(), phone: $('#af-phone').val() || null, license_no: $('#af-license').val() || null, bio: $('#af-bio').val() || null };
        var pwd = $('#af-password').val();
        if (pwd) data.password = pwd;

        var req = id ? api.put('/api/agents/' + id, data) : api.post('/api/agents', data);
        req.done(function () {
            Modal.close(); toast.success(id ? 'Agent updated.' : 'Agent created.');
            agentTable.refresh();
        }).fail(function (xhr) {
            var r = xhr.responseJSON || {};
            if (!r.errors && xhr.status !== 0 && xhr.status !== 401 && xhr.status !== 419 && xhr.status !== 422 && xhr.status < 500) toast.error(r.message || 'Could not save the agent.');
            if (r.errors) Object.keys(r.errors).forEach(function (f) { $('#aerr-' + f).text(Array.isArray(r.errors[f]) ? r.errors[f][0] : r.errors[f]).removeClass('hidden'); });
        }).always(function () {
            $btn.prop('disabled', false).removeAttr('aria-busy');
            $('#agent-save-text').text('Save');
            $('#agent-save-spin').addClass('hidden');
        });
    });

    $(document).on('click', '#btn-add-agent', openAddModal);
    $(document).on('click', '.btn-edit-agent', function () { openEditModal($(this).data('id')); });
    $(document).on('click', '.btn-del-agent', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        if (!confirm('Delete agent "' + name + '"?')) return;
        api.delete('/api/agents/' + id).done(function () {
            toast.success('Agent deleted.'); agentTable.refresh();
        }).fail(function (xhr) {
            toast.error((xhr.responseJSON || {}).message || 'Delete failed.');
        });
    });

    initTable();
});

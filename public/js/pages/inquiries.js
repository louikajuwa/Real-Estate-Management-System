const KanbanPage = (function () {
    let _inquiries = [];
    let _dragging = null;
    let _searchTimer = null;
    let _isAdmin = false;
    let _isAgent = false;

    function relativeTime(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        const d = new Date(dateStr);
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function makeCard(inq) {
        const clientName = inq.client ? (inq.client.name || 'Unknown') : 'Unknown';
        const propTitle = inq.property ? inq.property.title : '—';
        const card = document.createElement('div');
        card.className = 'inquiry-card bg-white rounded-lg shadow-sm p-3 cursor-pointer hover:shadow-md transition-shadow relative group';
        card.dataset.id = inq.id;
        card.dataset.status = inq.status;
        card.draggable = true;
        card.tabIndex = 0;
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', 'Inquiry from ' + clientName + ' about ' + propTitle + '. Press Enter to open details.');
        card.innerHTML =
            '<div class="text-sm font-semibold text-zinc-900 leading-tight">' + esc(clientName) + '</div>' +
            '<div class="text-xs text-zinc-500 truncate mt-0.5">' + esc(propTitle) + '</div>' +
            '<div class="text-xs text-zinc-400 mt-1">' + relativeTime(inq.created_at) + '</div>' +
            '<span class="drag-handle absolute top-2 right-2 text-zinc-300 opacity-0 group-hover:opacity-100 select-none text-base" aria-hidden="true">⠿</span>';
        card.addEventListener('dragstart', function (e) {
            _dragging = inq;
            e.dataTransfer.effectAllowed = 'move';
            card.classList.add('opacity-50');
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('opacity-50');
        });
        card.addEventListener('click', function () { showDetail(inq); });
        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showDetail(inq); }
        });
        return card;
    }

    function renderBoard(inquiries) {
        const statuses = ['new', 'contacted', 'viewing_scheduled', 'negotiation', 'closed'];
        const grouped = {};
        statuses.forEach(s => (grouped[s] = []));
        inquiries.forEach(inq => {
            if (grouped[inq.status] !== undefined) grouped[inq.status].push(inq);
        });

        let total = 0;
        statuses.forEach(function (status) {
            const lane = document.querySelector('.kanban-lane[data-status="' + status + '"]');
            const countEl = document.querySelector('.kanban-col[data-status="' + status + '"] .kanban-count');
            if (!lane) return;
            lane.innerHTML = '';
            const cards = grouped[status];
            if (!cards || cards.length === 0) {
                lane.innerHTML = '<p class="text-xs text-zinc-400 text-center py-4">No inquiries</p>';
            } else {
                cards.forEach(inq => lane.appendChild(makeCard(inq)));
                total += cards.length;
            }
            if (countEl) countEl.textContent = cards ? cards.length : 0;
        });

        document.getElementById('kanban-empty').classList.toggle('hidden', total > 0 || inquiries.length > 0);
    }

    function showSkeletons() {
        document.querySelectorAll('.kanban-lane').forEach(function (lane) {
            lane.innerHTML =
                '<div class="animate-pulse space-y-2 p-1"><div class="h-4 bg-zinc-200 rounded w-3/4"></div><div class="h-3 bg-zinc-200 rounded w-1/2"></div></div>' +
                '<div class="animate-pulse space-y-2 p-1 mt-2"><div class="h-4 bg-zinc-200 rounded w-2/3"></div><div class="h-3 bg-zinc-200 rounded w-1/3"></div></div>';
        });
    }

    function load() {
        const q = $('#inq-search').val();
        const propId = $('#inq-filter-property').val();
        const params = { per_page: 200 };
        if (q) params.q = q;
        if (propId) params.property_id = propId;
        showSkeletons();
        api('/api/inquiries', {
            method: 'GET',
            data: params,
            success: function (res) {
                _inquiries = res.data || [];
                renderBoard(_inquiries);
            },
            error: function () {
                toast.error('Failed to load inquiries.');
                document.querySelectorAll('.kanban-lane').forEach(l => (l.innerHTML = ''));
            },
        });
    }

    function changeStatus(inq, targetStatus, done) {
        if (inq.status === targetStatus) { if (done) done(false); return; }
        if (!_isAdmin && !_isAgent) {
            toast.error('You do not have permission to move inquiries.');
            if (done) done(false);
            return;
        }
        api('/api/inquiries/' + inq.id + '/status', {
            method: 'PATCH',
            data: JSON.stringify({ status: targetStatus }),
            contentType: 'application/json',
            success: function (res) {
                inq.status = targetStatus;
                const updated = res.data || res;
                const idx = _inquiries.findIndex(i => i.id === inq.id);
                if (idx !== -1) _inquiries[idx] = Object.assign(_inquiries[idx], updated);
                renderBoard(_inquiries);
                toast.success('Status updated.');
                if (done) done(true);
            },
            error: function (xhr) {
                if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419) { if (done) done(false); return; }
                const body = xhr.responseJSON || {};
                toast.error(body.message || 'Invalid status transition.');
                load();
                if (done) done(false);
            },
        });
    }

    function onDrop(event, targetStatus) {
        event.preventDefault();
        if (!_dragging) return;
        const inq = _dragging;
        _dragging = null;
        changeStatus(inq, targetStatus);
    }

    // Mirrors App\Enums\InquiryStatus::allowedTransitions().
    const TRANSITIONS = {
        new: ['contacted', 'closed'],
        contacted: ['viewing_scheduled', 'closed'],
        viewing_scheduled: ['negotiation', 'contacted', 'closed'],
        negotiation: ['closed', 'viewing_scheduled'],
        closed: [],
    };

    function showDetail(inq) {
        const clientName = inq.client ? inq.client.name : '—';
        const propTitle  = inq.property ? inq.property.title : '—';
        const agentName  = inq.agent && inq.agent.user ? inq.agent.user.name : '—';
        const body = document.getElementById('inq-detail-body');
        body.innerHTML =
            '<div><span class="font-medium text-zinc-500">Client:</span> ' + esc(clientName) + '</div>' +
            '<div><span class="font-medium text-zinc-500">Property:</span> ' + esc(propTitle) + '</div>' +
            '<div><span class="font-medium text-zinc-500">Agent:</span> ' + esc(agentName) + '</div>' +
            '<div><span class="font-medium text-zinc-500">Status:</span> <span class="capitalize">' + esc(String(inq.status).replace(/_/g, ' ')) + '</span></div>' +
            '<div><span class="font-medium text-zinc-500">Message:</span><p class="mt-1 text-zinc-600 whitespace-pre-wrap">' + esc(inq.message) + '</p></div>' +
            '<div><span class="font-medium text-zinc-500">Created:</span> ' + new Date(inq.created_at).toLocaleString() + '</div>';
        const allowed = TRANSITIONS[inq.status] || [];
        $('#inq-status-select option').each(function () {
            this.disabled = this.value !== inq.status && allowed.indexOf(this.value) === -1;
        });
        $('#inq-status-select').val(inq.status).data('inq', inq).prop('disabled', allowed.length === 0);
        $('#inq-status-row').toggleClass('hidden', !(_isAdmin || _isAgent));
        const delBtn = document.getElementById('btn-inq-delete');
        delBtn.classList.toggle('hidden', !_isAdmin);
        delBtn.onclick = function () { deleteInquiry(inq); };
        Modal.open('modal-inq-detail');
    }

    function deleteInquiry(inq) {
        if (!confirm('Delete this inquiry? This cannot be undone.')) return;
        const $del = $('#btn-inq-delete').prop('disabled', true);
        api('/api/inquiries/' + inq.id, {
            method: 'DELETE',
            success: function () {
                Modal.close('modal-inq-detail');
                toast.success('Inquiry deleted.');
                load();
            },
            error: function (xhr) {
                if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419) return;
                toast.error((xhr.responseJSON || {}).message || 'Could not delete the inquiry.');
            },
            complete: function () { $del.prop('disabled', false); },
        });
    }

    function populatePropertySelect(sel) {
        api('/api/properties', {
            method: 'GET',
            data: { per_page: 200 },
            success: function (res) {
                const props = res.data || [];
                props.forEach(function (p) {
                    $(sel).append($('<option>', { value: p.id, text: p.title }));
                });
            },
        });
    }

    function populateClientSelect() {
        api('/api/clients', {
            method: 'GET',
            data: { per_page: 200 },
            success: function (res) {
                const clients = res.data || [];
                clients.forEach(function (c) {
                    $('#inq-client-select').append($('<option>', { value: c.id, text: c.name }));
                });
            },
        });
    }

    function init() {
        const user = window.__USER__ || {};
        const roles = (user.roles || []).map(r => typeof r === 'string' ? r : r.name);
        _isAdmin = roles.includes('admin');
        _isAgent = roles.includes('agent');

        if (!_isAdmin && !_isAgent) {
            $('#btn-new-inquiry').hide();
            $('#inq-client-row').hide();
        }

        populatePropertySelect('#inq-filter-property');
        populatePropertySelect('#inq-property-select');
        if (_isAdmin) populateClientSelect();

        load();

        $('#inq-search').on('input', function () {
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(load, 350);
        });
        $('#inq-filter-property').on('change', load);

        // Drop targets (replaces former inline ondragover/ondrop attributes)
        document.querySelectorAll('.kanban-lane').forEach(function (lane) {
            lane.addEventListener('dragover', function (e) { e.preventDefault(); });
            lane.addEventListener('drop', function (e) { onDrop(e, lane.dataset.status); });
        });

        // Keyboard / touch alternative to drag-and-drop
        $('#inq-status-select').on('change', function () {
            const $s = $(this);
            const inq = $s.data('inq');
            if (!inq) return;
            $s.prop('disabled', true);
            changeStatus(inq, $s.val(), function (ok) {
                $s.prop('disabled', false);
                if (ok) Modal.close();
                else $s.val(inq.status);
            });
        });

        $('#btn-new-inquiry').on('click', function () {
            $('#form-inquiry')[0].reset();
            Modal.open('modal-inquiry');
        });

        $('#form-inquiry').on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-inq-submit');
            $btn.prop('disabled', true).attr('aria-busy', 'true').text('Saving…');
            $('.field-error').addClass('hidden').text('');
            const data = {};
            $(this).serializeArray().forEach(function (f) {
                if (f.value) data[f.name] = f.value;
            });
            api('/api/inquiries', {
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function () {
                    Modal.close('modal-inquiry');
                    toast.success('Inquiry submitted.');
                    load();
                },
                error: function (xhr) {
                    if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419) return;
                    const errs = (xhr.responseJSON || {}).errors || {};
                    if (!Object.keys(errs).length) toast.error((xhr.responseJSON || {}).message || 'Could not save the inquiry.');
                    Object.keys(errs).forEach(function (k) {
                        const el = $('[name="' + k + '"]').closest('div').find('.field-error');
                        var v = errs[k];
                        el.removeClass('hidden').text(Array.isArray(v) ? v[0] : v);
                    });
                },
                complete: function () {
                    $btn.prop('disabled', false).removeAttr('aria-busy').text('Save');
                },
            });
        });
    }

    return { init: init, onDrop: onDrop };
})();

$(function () { KanbanPage.init(); });

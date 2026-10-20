const BookingsPage = (function () {
    let _page = 1;
    let _perPage = 20;
    let _isAdmin = false;
    let _isAgent = false;

    const STATUS_BADGE = {
        scheduled: '<span class="badge badge-info">Scheduled</span>',
        completed:  '<span class="badge badge-success">Completed</span>',
        cancelled:  '<span class="badge badge-danger">Cancelled</span>',
    };

    function getFilters() {
        return {
            status:    $('#bk-filter-status').val() || undefined,
            date_from: $('#bk-filter-from').val()   || undefined,
            date_to:   $('#bk-filter-to').val()     || undefined,
            page:      _page,
            per_page:  _perPage,
        };
    }

    function renderRow(bk) {
        const propTitle  = bk.property ? esc(bk.property.title) : '—';
        const clientName = bk.client   ? esc(bk.client.name)   : '—';
        const agentName  = bk.agent && bk.agent.user ? esc(bk.agent.user.name) : '—';
        const scheduled  = bk.scheduled_at ? new Date(bk.scheduled_at).toLocaleString() : '—';
        const badge      = STATUS_BADGE[bk.status] || esc(bk.status);

        let actions = '';
        if (_isAdmin || _isAgent) {
            actions += '<button type="button" class="bk-act-status text-xs text-zinc-500 hover:text-indigo-600 mr-2 rounded" data-id="' + bk.id + '" data-title="' + esc(bk.property ? bk.property.title : '') + '">Status</button>';
        }
        if (_isAdmin) {
            actions += '<button type="button" class="bk-act-delete text-xs text-rose-500 hover:text-rose-700 rounded" data-id="' + bk.id + '">Delete</button>';
        }
        if (!_isAdmin && !_isAgent) {
            if (bk.status === 'scheduled') {
                actions += '<button type="button" class="bk-act-cancel text-xs text-rose-500 hover:text-rose-700 rounded" data-id="' + bk.id + '">Cancel</button>';
            }
        }

        return '<tr class="border-b border-zinc-50 hover:bg-zinc-50 transition-colors">' +
            '<td class="px-4 py-3 text-sm text-zinc-900">' + propTitle + '</td>' +
            '<td class="px-4 py-3 text-sm text-zinc-600">' + clientName + '</td>' +
            '<td class="px-4 py-3 text-sm text-zinc-600">' + agentName + '</td>' +
            '<td class="px-4 py-3 text-sm text-zinc-600 whitespace-nowrap">' + scheduled + '</td>' +
            '<td class="px-4 py-3 text-sm text-zinc-600">' + (bk.duration_min || 60) + ' min</td>' +
            '<td class="px-4 py-3">' + badge + '</td>' +
            '<td class="px-4 py-3 whitespace-nowrap">' + actions + '</td>' +
            '</tr>';
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.last_page <= 1) {
            $('#bk-pagination').addClass('hidden');
            return;
        }
        $('#bk-pagination').removeClass('hidden');
        $('#bk-page-info').text('Showing ' + pagination.from + '–' + pagination.to + ' of ' + pagination.total);
        renderPager($('#bk-page-btns'), pagination);
    }

    function load() {
        const $tbody = $('#bk-tbody');
        $tbody.html('<tr><td colspan="7" class="px-4 py-4">' +
            '<div class="flex flex-col gap-2">' +
            [1,2,3].map(() => '<div class="flex gap-4 animate-pulse">' +
                ['flex-1','flex-1','flex-1','w-36','w-20','w-24','w-16'].map(w =>
                    '<div class="h-4 bg-zinc-100 rounded ' + w + '"></div>'
                ).join('') + '</div>').join('') +
            '</div></td></tr>');

        api('/api/bookings', {
            method: 'GET',
            data: getFilters(),
            success: function (res) {
                const bookings = res.data || [];
                $tbody.empty();
                if (bookings.length === 0) {
                    $('#bk-empty').removeClass('hidden');
                    $('#bk-table-wrap table').addClass('hidden');
                    $('#bk-pagination').addClass('hidden');
                } else {
                    $('#bk-empty').addClass('hidden');
                    $('#bk-table-wrap table').removeClass('hidden');
                    bookings.forEach(function (bk) { $tbody.append(renderRow(bk)); });
                    renderPagination(res.pagination);
                }
            },
            error: function () {
                toast.error('Failed to load bookings.');
                $tbody.html('<tr><td colspan="7" class="px-4 py-4 text-sm text-zinc-400 text-center">Error loading bookings.</td></tr>');
            },
        });
    }

    function populatePropertySelect() {
        api('/api/properties', {
            method: 'GET',
            data: { per_page: 200 },
            success: function (res) {
                const props = res.data || [];
                $('#form-booking [name="property_id"]').empty().append('<option value="">Select a property…</option>');
                props.forEach(function (p) {
                    $('#form-booking [name="property_id"]').append($('<option>', { value: p.id, text: p.title }));
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
                $('#form-booking [name="client_id"]').empty().append('<option value="">Select a client…</option>');
                clients.forEach(function (c) {
                    $('#form-booking [name="client_id"]').append($('<option>', { value: c.id, text: c.name }));
                });
            },
        });
    }

    function openStatus(bookingId, propTitle) {
        $('#bk-status-booking-id').val(bookingId);
        $('#bk-status-context').text('Update status for booking on: ' + propTitle);
        Modal.open('modal-bk-status');
    }

    function setStatus(newStatus) {
        const id = $('#bk-status-booking-id').val();
        const $btns = $('#modal-bk-status [data-status]');
        $btns.prop('disabled', true);
        api('/api/bookings/' + id + '/status', {
            method: 'PATCH',
            data: JSON.stringify({ status: newStatus }),
            contentType: 'application/json',
            success: function () {
                Modal.close('modal-bk-status');
                toast.success('Status updated.');
                load();
            },
            error: function (xhr) {
                if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419) return;
                toast.error((xhr.responseJSON || {}).message || 'Could not update status.');
            },
            complete: function () { $btns.prop('disabled', false); },
        });
    }

    function cancelOwn(bookingId) {
        if (!confirm('Cancel this booking?')) return;
        api('/api/bookings/' + bookingId + '/status', {
            method: 'PATCH',
            data: JSON.stringify({ status: 'cancelled' }),
            contentType: 'application/json',
            success: function () {
                toast.success('Booking cancelled.');
                load();
            },
        });
    }

    function confirmDelete(bookingId) {
        if (!confirm('Delete this booking permanently?')) return;
        api('/api/bookings/' + bookingId, {
            method: 'DELETE',
            success: function () {
                toast.success('Booking deleted.');
                load();
            },
        });
    }

    function openNewBookingModal() {
        $('#form-booking')[0].reset();
        $('[name="_booking_id"]').val('');
        $('#modal-booking-title').text('New Booking');
        $('.field-error').addClass('hidden').text('');
        Modal.open('modal-booking');
    }

    function init() {
        const user = window.__USER__ || {};
        const roles = (user.roles || []).map(r => typeof r === 'string' ? r : r.name);
        _isAdmin = roles.includes('admin');
        _isAgent = roles.includes('agent');

        if (!_isAdmin && !_isAgent) {
            $('#bk-client-row').hide();
        }

        populatePropertySelect();
        if (_isAdmin) populateClientSelect();

        load();

        $('#bk-filter-status, #bk-filter-from, #bk-filter-to').on('change', function () {
            _page = 1;
            load();
        });

        $('#btn-new-booking').on('click', openNewBookingModal);
        $('#bk-empty-new').on('click', openNewBookingModal);

        $('#bk-page-btns').on('click', '[data-page]', function () {
            _page = parseInt($(this).data('page'), 10) || 1;
            load();
        });
        $('#bk-tbody').on('click', '.bk-act-status', function () {
            openStatus($(this).data('id'), $(this).data('title'));
        });
        $('#bk-tbody').on('click', '.bk-act-delete', function () { confirmDelete($(this).data('id')); });
        $('#bk-tbody').on('click', '.bk-act-cancel', function () { cancelOwn($(this).data('id')); });
        $('#modal-bk-status').on('click', '[data-status]', function () { setStatus($(this).data('status')); });

        $('#form-booking').on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-bk-submit');
            $btn.prop('disabled', true).attr('aria-busy', 'true').text('Saving…');
            $('.field-error').addClass('hidden').text('');
            const data = {};
            $(this).serializeArray().forEach(function (f) {
                if (f.name !== '_booking_id' && f.value !== '') data[f.name] = f.value;
            });
            // datetime-local is the viewer's local time; the server stores UTC.
            if (data.scheduled_at) data.scheduled_at = new Date(data.scheduled_at).toISOString();
            api('/api/bookings', {
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function () {
                    Modal.close('modal-booking');
                    toast.success('Booking confirmed.');
                    load();
                },
                error: function (xhr) {
                    const body = xhr.responseJSON || {};
                    const errs = body.errors || {};
                    Object.keys(errs).forEach(function (k) {
                        const el = $('[name="' + k + '"]').closest('div').find('.field-error');
                        el.removeClass('hidden').text(errs[k][0]);
                    });
                    if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419) return;
                    if (!Object.keys(errs).length) {
                        toast.error(body.message || 'Could not save the booking. Please try again.');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).removeAttr('aria-busy').text('Save');
                },
            });
        });
    }

    return { init: init, openStatus: openStatus, setStatus: setStatus, cancelOwn: cancelOwn, confirmDelete: confirmDelete };
})();

$(function () { BookingsPage.init(); });
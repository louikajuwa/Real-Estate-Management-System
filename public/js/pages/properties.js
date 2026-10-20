$(function () {
    var isAdmin = window.__USER__ && window.__USER__.roles && (window.__USER__.roles.indexOf('admin') !== -1);
    var currentView = 'table';
    var gridState = { page: 1, perPage: 9, sort: 'created_at', dir: 'desc', search: '', filters: {} };
    var propertyTable;

    // Resolve image src — supports both absolute URLs (demo picsum) and local storage paths
    function imgSrc(path) {
        if (!path) return null;
        return (path.indexOf('http') === 0) ? path : '/storage/' + path;
    }

    // Placeholder shown when an image fails to load
    var IMG_PLACEHOLDER = '<div class="w-full h-full bg-zinc-100 flex items-center justify-center"><svg class="w-6 h-6 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg></div>';
    var IMG_ONERROR = 'this.onerror=null;this.style.display=\'none\';this.insertAdjacentHTML(\'afterend\',\'' + IMG_PLACEHOLDER.replace(/'/g, "\\'") + '\');';

    // Status badge helper
    function statusBadge(status) {
        var map = { available: 'badge-success', reserved: 'badge-warning', sold: 'badge-danger' };
        var cls = map[status] || 'badge-neutral';
        return '<span class="badge ' + cls + '">' + (esc(status) || '—') + '</span>';
    }

    function typeBadge(type) {
        return '<span class="text-xs text-zinc-500 capitalize">' + (esc(type) || '—') + '</span>';
    }

    function fmtPrice(price) {
        if (!price) return '—';
        return 'AED ' + Number(price).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    // Table actions
    function tableActions(row) {
        var html = '<div class="flex items-center justify-end gap-1">';
        html += '<button type="button" class="btn-edit btn-icon hover:text-indigo-600" data-id="' + esc(row.id) + '" title="Edit" aria-label="Edit ' + esc(row.title) + '">';
        html += '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>';
        html += '<button type="button" class="btn-status btn-icon hover:text-amber-600" data-id="' + esc(row.id) + '" data-status="' + esc(row.status) + '" title="Change status" aria-label="Change status of ' + esc(row.title) + '">';
        html += '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg></button>';
        html += '<button type="button" class="btn-delete btn-icon hover:text-rose-600" data-id="' + esc(row.id) + '" data-title="' + esc(row.title) + '" title="Delete" aria-label="Delete ' + esc(row.title) + '">';
        html += '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
        html += '</div>';
        return html;
    }

    // Init table
    function initTable() {
        propertyTable = new Table({
            id: 'properties',
            container: '#property-table-container',
            endpoint: '/api/properties',
            defaultSort: 'created_at',
            defaultDir: 'desc',
            columns: [
                {
                    key: 'title', label: 'Property', sortable: true,
                    render: function(row) {
                        var imgHtml = '';
                        if (row.primary_image) {
                            var src = imgSrc(row.primary_image.path);
                            imgHtml = '<img src="' + esc(src) + '" class="w-10 h-8 object-cover rounded" alt="' + esc(row.title) + '" onerror="' + IMG_ONERROR.replace(/"/g, '&quot;') + '">';
                        } else {
                            imgHtml = '<div class="w-10 h-8 bg-zinc-100 rounded flex items-center justify-center"><svg class="w-4 h-4 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 16.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v10.5a1.5 1.5 0 001.5 1.5z"/></svg></div>';
                        }
                        return '<div class="flex items-center gap-3">' + imgHtml + '<div><p class="font-medium text-zinc-900">' + esc(row.title) + '</p><p class="text-xs text-zinc-400">' + esc(row.city || '') + '</p></div></div>';
                    }
                },
                { key: 'type', label: 'Type', sortable: true, render: function(r) { return typeBadge(r.type); } },
                { key: 'price', label: 'Price', sortable: true, render: function(r) { return '<span class="font-semibold text-indigo-600">' + fmtPrice(r.price) + '</span>'; } },
                { key: 'status', label: 'Status', sortable: true, render: function(r) { return statusBadge(r.status); } },
                { key: 'agent', label: 'Agent', sortable: false, render: function(r) { return r.agent && r.agent.user ? esc(r.agent.user.name) : '<span class="text-zinc-300">—</span>'; } },
            ],
            actions: tableActions,
        });
        propertyTable.init();
    }

    // Grid view
    function loadGrid() {
        var $grid = $('#property-grid');
        // Skeleton
        $grid.html(Array(6).fill('<div class="bg-white rounded-xl overflow-hidden shadow-sm animate-pulse"><div class="aspect-video bg-zinc-100"></div><div class="p-4 space-y-2"><div class="h-3 bg-zinc-100 rounded w-3/4"></div><div class="h-3 bg-zinc-100 rounded w-1/2"></div></div></div>').join(''));

        var params = $.extend({ page: gridState.page, per_page: gridState.perPage }, gridState.filters);
        api.get('/api/properties', params).done(function(res) {
            renderGrid(res.data || [], res.pagination);
        }).fail(function() {
            $grid.html('<div class="col-span-full flex flex-col items-center py-12 gap-2"><p class="text-sm font-semibold text-zinc-700">Failed to load properties</p></div>');
            toast.error('Failed to load properties.');
        });
    }

    function renderGrid(properties, pagination) {
        var $grid = $('#property-grid');
        if (!properties.length) {
            $grid.html('<div class="col-span-full flex flex-col items-center py-12 gap-2"><svg class="w-12 h-12 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg><p class="text-sm font-semibold text-zinc-700">No properties yet</p><p class="text-xs text-zinc-400">Add your first property to get started</p><button type="button" class="btn btn-primary btn-sm mt-2 btn-add-prop">Add Property</button></div>');
            return;
        }
        var html = properties.map(function(p) {
            var src = p.primary_image ? imgSrc(p.primary_image.path) : null;
            var altText = esc(p.title);
            var imgEl = src
                ? '<img src="' + esc(src) + '" class="absolute inset-0 w-full h-full object-cover" alt="' + altText + '" onerror="' + IMG_ONERROR.replace(/"/g, '&quot;') + '">'
                : '<div class="absolute inset-0 bg-zinc-100 flex items-center justify-center"><svg class="w-8 h-8 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 16.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v10.5a1.5 1.5 0 001.5 1.5z"/></svg></div>';
            var statusColor = { available: 'bg-emerald-500', reserved: 'bg-amber-400', sold: 'bg-rose-500' };
            return '<div class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow cursor-pointer" data-id="' + p.id + '">' +
                '<div class="relative aspect-video">' + imgEl +
                '<span class="absolute top-2 right-2 text-xs font-medium text-white px-2 py-0.5 rounded-full ' + (statusColor[p.status] || 'bg-zinc-500') + '">' + esc(p.status) + '</span></div>' +
                '<div class="p-4">' +
                '<p class="text-sm font-semibold text-zinc-900 truncate">' + esc(p.title) + '</p>' +
                '<p class="text-xs text-zinc-500 truncate mt-0.5">' + esc((p.city || '') + ' · ' + (p.type || '')) + '</p>' +
                '<p class="text-sm font-semibold text-indigo-600 mt-2">' + fmtPrice(p.price) + '</p>' +
                '</div></div>';
        }).join('');
        $grid.html(html);

        // Pagination
        if (pagination) {
            var $pg = $('#grid-pagination').empty();
            if (pagination.last_page > 1) { renderPager($pg, pagination); }
        }
    }

    // Modal helpers
    function openAddModal() {
        $('#property-id').val('');
        $('#property-form')[0].reset();
        $('#modal-title').text('Add Property');
        $('#image-thumbnails').empty();
        clearFieldErrors();
        if (isAdmin) { $('#agent-field-wrap').removeClass('hidden'); loadAgents(); }
        Modal.open('property-modal');
    }

    function openEditModal(id) {
        api.get('/api/properties/' + id).done(function(res) {
            var p = res.data;
            $('#property-id').val(p.id);
            $('#modal-title').text('Edit Property');
            $('#f-title').val(p.title);
            $('#f-type').val(p.type);
            $('#f-status').val(p.status);
            $('#f-price').val(p.price);
            $('#f-city').val(p.city);
            $('#f-address').val(p.address);
            $('#f-bedrooms').val(p.bedrooms);
            $('#f-bathrooms').val(p.bathrooms);
            $('#f-area').val(p.area_sqft);
            $('#f-featured').prop('checked', !!p.is_featured);
            $('#f-description').val(p.description);
            if (isAdmin) {
                $('#agent-field-wrap').removeClass('hidden');
                loadAgents(p.agent_id);
            }
            // Show existing images
            renderExistingImages(p.images || [], p.id);
            clearFieldErrors();
            Modal.open('property-modal');
        });
    }

    function renderExistingImages(images, propertyId) {
        var $thumb = $('#image-thumbnails').empty();
        images.forEach(function(img) {
            var $div = $('<div class="relative group">');
            $div.html('<img src="' + esc(imgSrc(img.path)) + '" class="w-16 h-12 object-cover rounded border ' + (img.is_primary ? 'border-indigo-400 ring-2 ring-indigo-300' : 'border-zinc-200') + '" alt="" onerror="this.onerror=null;this.classList.add(\'bg-zinc-100\');this.removeAttribute(\'src\');"><div class="hidden group-hover:flex group-focus-within:flex absolute inset-0 bg-black/50 rounded items-center justify-center gap-1"><button type="button" class="btn-set-primary text-white text-xs" data-img-id="' + esc(img.id) + '" data-prop-id="' + esc(propertyId) + '" title="Set primary" aria-label="Set as primary image"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg></button><button type="button" class="btn-del-img text-white text-xs" data-img-id="' + esc(img.id) + '" data-prop-id="' + esc(propertyId) + '" title="Delete" aria-label="Delete image"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button></div>');
            $thumb.append($div);
        });
    }

    function loadAgents(selectedId) {
        api.get('/api/agents').done(function(res) {
            var $sel = $('#f-agent').empty().append('<option value="">Select agent…</option>');
            (res.data || []).forEach(function(a) {
                var name = a.user ? a.user.name : ('Agent #' + a.id);
                $sel.append('<option value="' + a.id + '" ' + (a.id == selectedId ? 'selected' : '') + '>' + esc(name) + '</option>');
            });
        });
    }

    function clearFieldErrors() {
        $('.field-error').addClass('hidden').text('');
    }

    function showFieldErrors(errors) {
        var fieldMap = { title: 'err-title', type: 'err-type', price: 'err-price', city: 'err-city', address: 'err-address', agent_id: 'err-agent_id' };
        Object.keys(errors).forEach(function(field) {
            var id = fieldMap[field];
            if (id) $('#' + id).text(Array.isArray(errors[field]) ? errors[field][0] : errors[field]).removeClass('hidden');
        });
    }

    // Form submit
    $('#property-form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#property-id').val();
        var $btn = $('#property-save-btn');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).attr('aria-busy', 'true');
        $('#save-btn-text').text('Saving…');
        $('#save-spinner').removeClass('hidden');
        clearFieldErrors();

        var data = {
            title: $('#f-title').val(),
            type: $('#f-type').val(),
            status: $('#f-status').val(),
            price: parseFloat($('#f-price').val()),
            city: $('#f-city').val(),
            address: $('#f-address').val(),
            bedrooms: $('#f-bedrooms').val() ? parseInt($('#f-bedrooms').val()) : null,
            bathrooms: $('#f-bathrooms').val() ? parseInt($('#f-bathrooms').val()) : null,
            area_sqft: $('#f-area').val() ? parseInt($('#f-area').val()) : null,
            is_featured: $('#f-featured').is(':checked'),
            description: $('#f-description').val()
        };
        if (isAdmin && $('#f-agent').val()) data.agent_id = parseInt($('#f-agent').val());

        var req = id ? api.put('/api/properties/' + id, data) : api.post('/api/properties', data);
        req.done(function(res) {
            Modal.close();
            toast.success(id ? 'Property updated.' : 'Property created.');
            if (currentView === 'table' && propertyTable) propertyTable.refresh();
            else loadGrid();
            // Upload pending images
            var files = $('#image-input')[0].files;
            if (files.length && res.data) {
                uploadImages(res.data.id, files);
            }
        }).fail(function(xhr) {
            var r = xhr.responseJSON || {};
            if (r.errors) showFieldErrors(r.errors);
            else if (xhr.status !== 0 && xhr.status !== 401 && xhr.status !== 419 && xhr.status !== 422 && xhr.status < 500) toast.error(r.message || 'Could not save the property.');
        }).always(function() {
            $btn.prop('disabled', false).removeAttr('aria-busy');
            $('#save-btn-text').text('Save');
            $('#save-spinner').addClass('hidden');
        });
    });

    function uploadImages(propId, files) {
        var fd = new FormData();
        for (var i = 0; i < files.length; i++) fd.append('images[]', files[i]);
        api.upload('/api/properties/' + propId + '/images', fd)
            .done(function() { toast.success('Images uploaded.'); if (propertyTable) propertyTable.refresh(); })
            .fail(function() { toast.error('Image upload failed.'); });
    }

    // Image dropzone
    $('#image-dropzone').on('click', function(e) { if (e.target.id !== 'image-input') $('#image-input').click(); });
    $('#image-dropzone').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $('#image-input').click(); }
    });
    $('#image-input').on('change', function() {
        var $thumb = $('#image-thumbnails').empty();
        var files = this.files;
        for (var i = 0; i < files.length; i++) {
            var url = URL.createObjectURL(files[i]);
            $thumb.append('<div class="relative"><img src="' + url + '" class="w-16 h-12 object-cover rounded border border-zinc-200">' + (i === 0 ? '<span class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full border border-white"></span>' : '') + '</div>');
        }
    });

    // Delete existing image
    $(document).on('click', '.btn-del-img', function() {
        var imgId = $(this).data('img-id'), propId = $(this).data('prop-id');
        if (!confirm('Delete this image?')) return;
        api.delete('/api/properties/' + propId + '/images/' + imgId).done(function() {
            toast.success('Image deleted.'); openEditModal(propId);
        });
    });

    // Set primary image
    $(document).on('click', '.btn-set-primary', function() {
        var imgId = $(this).data('img-id'), propId = $(this).data('prop-id');
        api.patch('/api/properties/' + propId + '/images/' + imgId + '/primary').done(function() {
            toast.success('Primary image updated.'); openEditModal(propId);
        });
    });

    // Table event delegation
    $(document).on('click', '.btn-edit', function() { openEditModal($(this).data('id')); });
    $(document).on('click', '.btn-add-prop, #add-property-table-btn, #btn-add-property', function() { openAddModal(); });

    $(document).on('click', '.btn-status', function() {
        var id = $(this).data('id'), status = $(this).data('status');
        $('#status-property-id').val(id);
        $('input[name="new-status"]').prop('checked', false);
        $('input[name="new-status"][value="' + status + '"]').prop('checked', true);
        Modal.open('status-modal');
    });

    $('#status-save-btn').on('click', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        var id = $('#status-property-id').val(), status = $('input[name="new-status"]:checked').val();
        if (!status) { toast.warning('Choose a status first.'); return; }
        busy($btn, api.patch('/api/properties/' + id + '/status', { status: status })).done(function() {
            Modal.close(); toast.success('Status updated.');
            if (currentView === 'table' && propertyTable) propertyTable.refresh();
            else loadGrid();
        }).fail(function(xhr) {
            if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419 || xhr.status === 422 || xhr.status >= 500) return;
            toast.error((xhr.responseJSON || {}).message || 'Could not update status.');
        });
    });

    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id'), title = $(this).data('title');
        if (!confirm('Delete property "' + title + '"? This cannot be undone.')) return;
        api.delete('/api/properties/' + id).done(function() {
            toast.success('Property deleted.');
            if (currentView === 'table' && propertyTable) propertyTable.refresh();
            else loadGrid();
        });
    });

    // View toggle
    $('#view-table').on('click', function() {
        currentView = 'table';
        $('#table-view').removeClass('hidden'); $('#grid-view').addClass('hidden');
        $(this).addClass('bg-indigo-50 text-indigo-600').removeClass('text-zinc-400');
        $('#view-grid').removeClass('bg-indigo-50 text-indigo-600').addClass('text-zinc-400').attr('aria-pressed', 'false');
        $(this).attr('aria-pressed', 'true');
    });
    $('#view-grid').on('click', function() {
        currentView = 'grid';
        $('#grid-view').removeClass('hidden'); $('#table-view').addClass('hidden');
        $(this).addClass('bg-indigo-50 text-indigo-600').removeClass('text-zinc-400');
        $('#view-table').removeClass('bg-indigo-50 text-indigo-600').addClass('text-zinc-400').attr('aria-pressed', 'false');
        $(this).attr('aria-pressed', 'true');
        loadGrid();
    });
    $('#grid-pagination').on('click', '[data-page]', function() {
        gridState.page = parseInt($(this).data('page')); loadGrid();
    });

    // Filter controls
    var filterTimer;
    function applyFilters() {
        var f = {
            status: $('#filter-status').val(),
            type: $('#filter-type').val(),
            city: $('#filter-city').val()
        };
        gridState.filters = f;
        if (currentView === 'table' && propertyTable) {
            propertyTable.state.filters = f;
            propertyTable.state.page = 1;
            propertyTable.load();
        } else {
            gridState.page = 1; loadGrid();
        }
    }
    $('#filter-status, #filter-type').on('change', applyFilters);
    $('#filter-city').on('input', function() { clearTimeout(filterTimer); filterTimer = setTimeout(applyFilters, 350); });

    // Init
    initTable();
});
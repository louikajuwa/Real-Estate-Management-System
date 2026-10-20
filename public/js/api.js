// Global HTML escaper: safe for text nodes AND quoted attribute values.
window.escHtml = window.esc = function (s) {
    return String(s === null || s === undefined ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
};

// api.js — unified AJAX wrapper
// Usage A: api('/path', { method, data, contentType, success, error, complete })
// Usage B: api.get(url), api.post(url, data), api.patch(url, data), api.delete(url)
// Global HTML escaper: safe for text nodes AND quoted attribute values.
window.escHtml = window.esc = function (s) {
    return String(s === null || s === undefined ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
};

// Disable a button (and flag aria-busy) while a jqXHR is in flight; always re-enables.
window.busy = function ($btn, req) {
    $btn.prop('disabled', true).attr('aria-busy', 'true');
    return req.always(function () { $btn.prop('disabled', false).removeAttr('aria-busy'); });
};

window.api = (function () {
    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function handleFail(xhr) {
        if (xhr.status === 0 && xhr.statusText !== 'abort') {
            if (typeof toast !== 'undefined') toast.error('Network error. Check your connection and try again.');
            return;
        }
        if (xhr.status === 419 || xhr.status === 401) {
            window.location.href = '/login';
            return;
        }
        if (xhr.status === 422) {
            var res = xhr.responseJSON || {};
            if (res.message && typeof toast !== 'undefined') toast.warning(res.message);
            return;
        }
        if (xhr.status >= 500 && typeof toast !== 'undefined') {
            toast.error('Something went wrong. Please try again.');
        }
    }

    // Core function — called as api(url, options)
    function apiFn(url, options) {
        options = options || {};
        var method = (options.method || 'GET').toUpperCase();
        var ajaxOpts = {
            url: url,
            method: method,
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        };
        if (options.contentType !== undefined) {
            ajaxOpts.contentType = options.contentType;
        }
        if (options.data !== undefined) {
            ajaxOpts.data = options.data;
        }
        if (options.success)  ajaxOpts.success  = options.success;
        if (options.error)    ajaxOpts.error     = options.error;
        if (options.complete) ajaxOpts.complete  = options.complete;

        var deferred = $.ajax(ajaxOpts);
        if (!options.error) {
            deferred.fail(handleFail);
        } else {
            // Custom error handlers still get session-expiry and network-failure handling.
            deferred.fail(function (xhr) {
                if (xhr.status === 0 || xhr.status === 401 || xhr.status === 419) handleFail(xhr);
            });
        }
        return deferred;
    }

    // Shorthand helpers (used in app layout inline JS)
    apiFn.get = function (url, params) {
        return $.ajax({
            url: url, method: 'GET', data: params,
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        }).fail(handleFail);
    };
    apiFn.post = function (url, data) {
        return apiFn(url, { method: 'POST', data: JSON.stringify(data), contentType: 'application/json' });
    };
    apiFn.put = function (url, data) {
        return apiFn(url, { method: 'PUT', data: JSON.stringify(data), contentType: 'application/json' });
    };
    apiFn.patch = function (url, data) {
        return apiFn(url, { method: 'PATCH', data: JSON.stringify(data), contentType: 'application/json' });
    };
    apiFn.delete = function (url) {
        return apiFn(url, { method: 'DELETE' });
    };
    apiFn.upload = function (url, formData) {
        return $.ajax({
            url: url, method: 'POST',
            data: formData,
            processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        }).fail(handleFail);
    };

    return apiFn;
})();

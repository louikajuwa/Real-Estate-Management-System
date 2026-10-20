window.Modal = (function() {
    var $current = null;
    var $trigger = null;
    var FOCUSABLE = 'a[href], button:not(:disabled), input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])';

    function visible($els) {
        return $els.filter(function() { return $(this).is(':visible'); });
    }

    function open(id) {
        $trigger = $(document.activeElement);
        $current = $('#' + id);
        // Dialog semantics fallback for any modal that does not declare them in markup.
        var $dialog = $current.children().first();
        if (!$dialog.attr('role')) $dialog.attr({ role: 'dialog', 'aria-modal': 'true' });
        if (!$dialog.attr('aria-labelledby') && !$dialog.attr('aria-label')) {
            var $h = $dialog.find('h1, h2, h3').first();
            if ($h.length) {
                if (!$h.attr('id')) $h.attr('id', id + '-heading');
                $dialog.attr('aria-labelledby', $h.attr('id'));
            }
        }
        $current.removeClass('hidden').addClass('flex');
        // Focus: [autofocus], else first visible form field, else first focusable control.
        var $focus = visible($current.find('[autofocus]')).first();
        if (!$focus.length) $focus = visible($current.find('input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled)')).first();
        if (!$focus.length) $focus = visible($current.find(FOCUSABLE)).first();
        $focus.trigger('focus');
        $('body').addClass('overflow-hidden');
    }

    function close() {
        if (!$current) return;
        $current.addClass('hidden').removeClass('flex');
        $current = null;
        $('body').removeClass('overflow-hidden');
        if ($trigger && $trigger.length && $.contains(document.documentElement, $trigger[0])) $trigger.trigger('focus');
        $trigger = null;
    }

    $(document).on('keydown', function(e) {
        if (!$current) return;
        if (e.key === 'Escape') { close(); return; }
        // Keep Tab focus inside the open dialog.
        if (e.key === 'Tab') {
            var $items = visible($current.find(FOCUSABLE));
            if (!$items.length) return;
            var first = $items[0], last = $items[$items.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            else if (!$.contains($current[0], document.activeElement)) { e.preventDefault(); first.focus(); }
        }
    });

    $(document).on('click', '[data-modal-close]', close);
    $(document).on('click', '.modal-backdrop', function(e) {
        if ($(e.target).hasClass('modal-backdrop')) close();
    });

    return { open: open, close: close };
})();

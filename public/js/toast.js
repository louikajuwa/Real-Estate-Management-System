(function() {
    var toasts = [];
    var MAX = 4;

    window.toast = function(message, type) {
        type = type || 'info';
        if (toasts.length >= MAX) {
            var oldest = toasts.shift();
            oldest.dismiss();
        }
        var colors = {
            success: { border: 'border-emerald-200', accent: 'bg-emerald-500', text: 'text-emerald-700' },
            error:   { border: 'border-rose-200',    accent: 'bg-rose-500',    text: 'text-rose-700' },
            warning: { border: 'border-amber-200',   accent: 'bg-amber-400',   text: 'text-amber-700' },
            info:    { border: 'border-zinc-200',     accent: 'bg-zinc-400',    text: 'text-zinc-700' }
        };
        var c = colors[type] || colors.info;
        var ttl = (type === 'error') ? 8000 : 5000;

        var $t = $('<div class="pointer-events-auto flex items-start gap-3 bg-white rounded-xl shadow-md px-4 py-3 border ' + c.border + ' max-w-xs w-full translate-x-full opacity-0 transition-all duration-150">');
        $t.html('<div class="w-1 self-stretch rounded-full shrink-0 ' + c.accent + '"></div><p class="flex-1 text-xs ' + c.text + ' leading-snug pt-0.5">' + esc(message) + '</p><button class="text-zinc-300 hover:text-zinc-500 ml-1 shrink-0 mt-0.5" aria-label="Dismiss">&times;</button>');

        var dismissed = false;
        var timer;
        function dismiss() {
            if (dismissed) return;
            dismissed = true;
            clearTimeout(timer);
            $t.addClass('translate-x-full opacity-0');
            setTimeout(function() { $t.remove(); }, 200);
            toasts = toasts.filter(function(x) { return x.$el !== $t; });
        }

        $t.find('button').on('click', dismiss);
        $t.data('dismiss', dismiss);

        $('#toast-container').append($t);
        requestAnimationFrame(function() {
            requestAnimationFrame(function() { $t.removeClass('translate-x-full opacity-0'); });
        });

        timer = setTimeout(dismiss, ttl);
        var obj = { $el: $t, dismiss: dismiss };
        toasts.push(obj);
    };

    window.toast.success = function(m) { window.toast(m, 'success'); };
    window.toast.error   = function(m) { window.toast(m, 'error'); };
    window.toast.warning = function(m) { window.toast(m, 'warning'); };
    window.toast.info    = function(m) { window.toast(m, 'info'); };
})();

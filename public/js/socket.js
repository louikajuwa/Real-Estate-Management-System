(function () {
    if (!window.__SOCKET_URL__ || !window.__SOCKET_TOKEN__) return;

    var socket = io(window.__SOCKET_URL__, {
        query: { token: window.__SOCKET_TOKEN__ },
        transports: ['websocket', 'polling'],
        reconnectionDelay: 1000,
        reconnectionDelayMax: 5000,
    });

    window.__SOCKET__ = socket;

    var $dot = $('#socket-status-dot');
    var _hideTimer = null;

    function showReconnecting() {
        clearTimeout(_hideTimer);
        $dot.removeClass('hidden bg-emerald-500').css('background-color', '#fbbf24').addClass('animate-pulse');
        $dot.removeClass('hidden');
    }

    function showConnected() {
        clearTimeout(_hideTimer);
        $dot.removeClass('animate-pulse').css('background-color', '#10b981');
        $dot.removeClass('hidden');
        _hideTimer = setTimeout(function () { $dot.addClass('hidden'); }, 3000);
        if (typeof loadNotifications === 'function') loadNotifications();
    }

    socket.on('connect',   showConnected);
    socket.on('reconnect', showConnected);

    socket.on('disconnect',    showReconnecting);
    socket.on('connect_error', showReconnecting);

    socket.on('notification', function (payload) {
        var $badge = $('#notification-badge');
        var current = parseInt($badge.text()) || 0;
        if (typeof window.updateBadge === 'function') window.updateBadge(current + 1);
        if (typeof toast !== 'undefined') {
            toast.info((payload.title || 'Notification') + ': ' + (payload.message || ''));
        }
    });

    socket.on('chat_message', function (payload) {
        if (window.ChatModule && typeof window.ChatModule.handleIncoming === 'function') {
            window.ChatModule.handleIncoming(payload);
        }
    });
})();

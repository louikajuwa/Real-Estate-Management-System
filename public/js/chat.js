window.ChatModule = (function () {
    var _unread = {};
    var _activeUserId = null;
    var _onIncoming = null;

    function setActive(userId) {
        _activeUserId = userId ? String(userId) : null;
        if (userId) {
            _unread[String(userId)] = 0;
            _updateBadge(String(userId));
        }
    }

    function setIncomingHandler(fn) {
        _onIncoming = fn;
    }

    function handleIncoming(msg) {
        var sid = String(msg.senderId);
        if (_activeUserId && sid === _activeUserId) {
            if (_onIncoming) _onIncoming(msg);
        } else {
            _unread[sid] = (_unread[sid] || 0) + 1;
            _updateBadge(sid);
            if (typeof toast !== 'undefined') {
                toast.info('New message from ' + (msg.senderName || 'User'));
            }
        }
    }

    function getUnread(userId) {
        return _unread[String(userId)] || 0;
    }

    function clearUnread(userId) {
        _unread[String(userId)] = 0;
        _updateBadge(String(userId));
    }

    function _updateBadge(userId) {
        var count = _unread[userId] || 0;
        var $badge = $('[data-contact-badge="' + userId + '"]');
        if ($badge.length) {
            if (count > 0) $badge.text(count > 99 ? '99+' : count).removeClass('hidden');
            else $badge.addClass('hidden');
        }
    }

    return {
        setActive: setActive,
        setIncomingHandler: setIncomingHandler,
        handleIncoming: handleIncoming,
        getUnread: getUnread,
        clearUnread: clearUnread,
    };
})();

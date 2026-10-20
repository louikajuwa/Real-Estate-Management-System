$(function () {
    var _contacts = [];
    var _activeUser = null;
    var _messages = [];
    var _seenIds = {};
    var _page = 1;
    var _hasMore = true;
    var _loadingMore = false;
    var _isMobile = false;

    function relTime(iso) {
        var diff = Math.floor((Date.now() - new Date(iso)) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        var d = new Date(iso);
        return d.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function myId() {
        return window.__USER__ ? String(window.__USER__.id) : null;
    }

    function makeBubble(msg) {
        var isMine = String(msg.sender_id || msg.senderId) === myId();
        var text = esc(msg.message);
        var time = relTime(msg.created_at || new Date().toISOString());
        if (isMine) {
            return '<div class="flex justify-end mb-2">' +
                '<div class="max-w-xs">' +
                '<div class="bg-indigo-600 text-white rounded-xl rounded-br-sm px-3 py-2 text-sm leading-snug">' + text + '</div>' +
                '<p class="text-[10px] text-zinc-400 text-right mt-0.5">' + time + '</p>' +
                '</div></div>';
        } else {
            return '<div class="flex justify-start mb-2">' +
                '<div class="max-w-xs">' +
                '<div class="bg-zinc-100 text-zinc-900 rounded-xl rounded-bl-sm px-3 py-2 text-sm leading-snug">' + text + '</div>' +
                '<p class="text-[10px] text-zinc-400 mt-0.5">' + time + '</p>' +
                '</div></div>';
        }
    }

    function isNearBottom() {
        var $thread = $('#chat-thread');
        var el = $thread[0];
        if (!el) return true;
        return el.scrollHeight - el.scrollTop - el.clientHeight < 100;
    }

    function scrollToBottom() {
        var el = $('#chat-thread')[0];
        if (el) el.scrollTop = el.scrollHeight;
        $('#chat-new-pill').addClass('hidden');
    }

    function appendMessage(msg) {
        var id = msg.id || msg.message_id;
        if (id && _seenIds[id]) return;
        if (id) _seenIds[id] = true;

        var near = isNearBottom();
        $('#chat-thread').append(makeBubble(msg));
        if (near) {
            scrollToBottom();
        } else {
            $('#chat-new-pill').removeClass('hidden');
        }
    }

    function renderContacts(contacts) {
        var $list = $('#contact-list');
        $list.empty();
        if (!contacts.length) {
            $list.html('<div class="flex flex-col items-center justify-center py-12 px-4">' +
                '<svg class="w-10 h-10 text-zinc-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>' +
                '<p class="text-xs text-zinc-500 text-center">No conversations yet</p></div>');
            return;
        }
        contacts.forEach(function (c) {
            var unread = window.ChatModule ? window.ChatModule.getUnread(c.id) : 0;
            var initials = (c.name || 'U').split(' ').slice(0,2).map(function(w){return w[0];}).join('').toUpperCase();
            var $row = $('<button type="button">').addClass('w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-zinc-50 transition-colors border-b border-zinc-50 last:border-0');
            $row.attr('data-user-id', c.id);
            $row.html(
                '<div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-semibold shrink-0">' + esc(initials) + '</div>' +
                '<div class="flex-1 min-w-0">' +
                '<p class="text-sm font-medium text-zinc-900 truncate">' + esc(c.name) + '</p>' +
                '<p class="text-xs text-zinc-400 truncate">' + esc(c.email || '') + '</p>' +
                '</div>' +
                '<span class="' + (unread > 0 ? '' : 'hidden') + ' shrink-0 min-w-[18px] h-[18px] flex items-center justify-center px-1 rounded-full bg-indigo-600 text-white text-[10px] font-semibold" data-contact-badge="' + c.id + '">' + (unread || '') + '</span>'
            );
            $row.on('click', function () { openThread(c); });
            $list.append($row);
        });
    }

    function openThread(user) {
        _activeUser = user;
        _messages = [];
        _seenIds = {};
        _page = 1;
        _hasMore = true;

        if (window.ChatModule) window.ChatModule.setActive(user.id);

        $('#contact-list button').removeClass('bg-indigo-50');
        $('[data-user-id="' + user.id + '"]').addClass('bg-indigo-50');

        $('#thread-name').text(user.name);
        $('#chat-thread').empty();
        $('#chat-input-area').removeClass('hidden');
        $('#thread-empty').addClass('hidden');
        $('#chat-thread-panel').removeClass('hidden');

        if (window.innerWidth < 768) {
            $('#contacts-panel').addClass('hidden');
            $('#thread-panel').removeClass('hidden');
        }

        loadMessages();
    }

    function loadMessages() {
        if (_loadingMore || !_hasMore) return;
        _loadingMore = true;
        api('/api/chat/' + _activeUser.id + '/messages', {
            method: 'GET',
            data: { page: _page, per_page: 20 },
            success: function (res) {
                var msgs = (res.data || []).slice().reverse();
                var pagination = res.pagination || {};
                _hasMore = !!pagination.next_page_url || (_page < (pagination.last_page || 1));

                if (_page === 1) {
                    $('#chat-thread').empty();
                    msgs.forEach(function (m) {
                        var id = m.id;
                        if (id) _seenIds[id] = true;
                        $('#chat-thread').append(makeBubble(m));
                    });
                    scrollToBottom();
                } else {
                    var prevHeight = $('#chat-thread')[0].scrollHeight;
                    msgs.forEach(function (m) {
                        var id = m.id;
                        if (id) _seenIds[id] = true;
                        $('#chat-thread').prepend(makeBubble(m));
                    });
                    var newHeight = $('#chat-thread')[0].scrollHeight;
                    $('#chat-thread')[0].scrollTop = newHeight - prevHeight;
                }
                _page++;
            },
            complete: function () { _loadingMore = false; },
        });
    }

    function sendMessage() {
        var text = $('#chat-message-input').val().trim();
        if (!text || !_activeUser) return;
        $('#chat-message-input').val('').css('height', '40px');
        $('#btn-send').prop('disabled', true);

        api('/api/chat', {
            method: 'POST',
            data: JSON.stringify({ receiver_id: _activeUser.id, message: text }),
            contentType: 'application/json',
            success: function (res) {
                var msg = res.data || {};
                appendMessage(msg);
            },
            error: function () {
                toast.error('Failed to send message.');
                $('#chat-message-input').val(text);
            },
            complete: function () {
                $('#btn-send').prop('disabled', false);
            },
        });
    }

    function loadContacts() {
        $('#contact-list').html(
            '<div class="space-y-3 p-4">' +
            [1,2,3].map(function () {
                return '<div class="flex items-center gap-3 animate-pulse">' +
                    '<div class="w-9 h-9 rounded-full bg-zinc-100"></div>' +
                    '<div class="flex-1 space-y-1">' +
                    '<div class="h-3 bg-zinc-100 rounded w-3/4"></div>' +
                    '<div class="h-3 bg-zinc-100 rounded w-1/2"></div>' +
                    '</div></div>';
            }).join('') + '</div>'
        );
        api('/api/chat/users', {
            method: 'GET',
            success: function (res) {
                _contacts = res.data || [];
                renderContacts(_contacts);
            },
        });
    }

    // Incoming socket message handler
    if (window.ChatModule) {
        window.ChatModule.setIncomingHandler(function (msg) {
            if (_activeUser && String(msg.senderId || msg.sender_id) === String(_activeUser.id)) {
                appendMessage(msg);
            }
        });
    }

    // Scroll up to load more
    $('#chat-thread').on('scroll', function () {
        if (this.scrollTop < 60) loadMessages();
    });

    // Send on Enter (Shift+Enter = newline)
    $('#chat-message-input').on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-grow textarea
    $('#chat-message-input').on('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 96) + 'px';
    });

    $('#btn-send').on('click', sendMessage);

    // New message pill
    $('#chat-new-pill').on('click', scrollToBottom);

    // Mobile back button
    $('#btn-back-contacts').on('click', function () {
        $('#thread-panel').addClass('hidden');
        $('#contacts-panel').removeClass('hidden');
        if (window.ChatModule) window.ChatModule.setActive(null);
        _activeUser = null;
    });

    // Responsive check
    function checkMobile() { _isMobile = window.innerWidth < 768; }
    checkMobile();
    $(window).on('resize', checkMobile);

    loadContacts();
});

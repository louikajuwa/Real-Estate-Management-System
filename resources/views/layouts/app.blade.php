<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Real Estate') — RealEstate</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.__SOCKET_URL__ = @json(config('services.socket.url', 'http://localhost:6001'));
        @auth
        window.__USER__ = {
            id: {{ auth()->id() }},
            name: @json(auth()->user()->name),
            roles: @json(auth()->user()->getRoleNames())
        };
        @php
            $userId = auth()->id();
            $timestamp = now()->timestamp;
            $hmac = hash_hmac('sha256', $userId . '|' . $timestamp, config('app.key'));
        @endphp
        window.__SOCKET_TOKEN__ = @json($userId . '|' . $timestamp . '|' . $hmac);
        @endauth
    </script>
</head>
<body class="h-full font-sans antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:px-3 focus:py-2 focus:bg-white focus:rounded-lg focus:shadow-md text-sm font-medium text-indigo-700">Skip to content</a>
<div class="flex h-full" id="app-shell">

    {{-- Mobile overlay --}}
    <div class="fixed inset-0 z-40 bg-zinc-900/50 hidden lg:hidden" id="sidebar-overlay" aria-hidden="true"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex flex-col w-60 bg-white border-r border-zinc-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200" id="sidebar">
        {{-- Logo --}}
        <div class="flex items-center h-16 px-6 border-b border-zinc-100">
            <span class="text-base font-semibold text-zinc-900">RealEstate</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5" aria-label="Main navigation">
            @php
                $navItems = [
                    ['route' => 'dashboard',   'label' => 'Dashboard',   'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route' => 'properties',  'label' => 'Properties',  'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['route' => 'agents',      'only' => 'staff', 'label' => 'Agents',      'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['route' => 'clients',     'only' => 'staff', 'label' => 'Clients',     'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['route' => 'inquiries',   'label' => 'Inquiries',   'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                    ['route' => 'bookings',    'label' => 'Bookings',    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['route' => 'chat',        'label' => 'Chat',        'icon' => 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z'],
                    ['route' => 'logs',        'only' => 'admin', 'label' => 'Logs',        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ];
            @endphp

            @auth
            @if(auth()->user()->isSuperAdmin())
                {{-- Super admin nav --}}
                @php
                    $superNavItems = [
                        ['route' => 'super.dashboard',  'label' => 'Platform Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'super.agencies',   'label' => 'Agencies',           'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                    ];
                @endphp
                <p class="px-3 pt-2 pb-1 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Platform</p>
                @foreach($superNavItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @if($active) aria-current="page" @endif
                       class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ $active ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">
                        <svg class="w-4 h-4 shrink-0 {{ $active ? 'text-indigo-600' : 'text-zinc-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @else
            @foreach($navItems as $item)
                @continue(($item['only'] ?? null) === 'staff' && ! auth()->user()->isAgencyStaff())
                @continue(($item['only'] ?? null) === 'admin' && ! auth()->user()->isAdmin())
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                       @if($active) aria-current="page" @endif
                   class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ $active ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">
                    <svg class="w-4 h-4 shrink-0 {{ $active ? 'text-indigo-600' : 'text-zinc-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
            @endif
            @endauth
        </nav>

        {{-- User footer --}}
        <div class="p-4 border-t border-zinc-100">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                    <span class="text-xs font-semibold text-indigo-600">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-zinc-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-zinc-400 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
                <button id="logout-btn" type="button" class="btn-icon" title="Sign out" aria-label="Sign out">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </div>
        </div>
    </aside>

    {{-- Main area --}}
    <div class="flex flex-col flex-1 min-w-0 lg:ml-60">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex items-center h-16 px-4 lg:px-6 bg-white border-b border-zinc-200 gap-4">
            {{-- Mobile hamburger --}}
            <button type="button" class="btn-icon lg:hidden p-2 -ml-2 text-zinc-500" id="sidebar-toggle" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Page title --}}
            <h1 class="text-sm font-semibold text-zinc-900 flex-1">@yield('page-title', 'Dashboard')</h1>

            {{-- Right actions --}}
            <div class="flex items-center gap-2">
                {{-- Notification bell --}}
                <div class="relative" id="notification-container">
                    <button id="notification-bell" type="button" class="btn-icon relative p-2 text-zinc-500 rounded-lg hover:bg-zinc-100" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" aria-controls="notification-dropdown">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        {{-- Socket status dot --}}
                        <span id="socket-status-dot" class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-400 animate-pulse hidden"></span>
                        {{-- Unread badge --}}
                        <span id="notification-badge"
                              aria-live="polite"
                              aria-atomic="true"
                              class="absolute -top-0.5 -right-0.5 hidden min-w-[18px] h-[18px] flex items-center justify-center px-1 rounded-full bg-indigo-600 text-white text-[10px] font-semibold leading-none"></span>
                    </button>

                    {{-- Notification dropdown --}}
                    <div id="notification-dropdown" class="hidden absolute right-0 mt-1 w-80 bg-white rounded-xl shadow-lg border border-zinc-100 z-50">
                        <div class="px-4 py-3 flex items-center justify-between border-b border-zinc-100">
                            <span class="text-sm font-semibold text-zinc-900">Notifications</span>
                            <button id="mark-all-read" type="button" class="text-xs text-indigo-600 hover:text-indigo-700 rounded">Mark all read</button>
                        </div>
                        <div id="notification-list" class="max-h-80 overflow-y-auto">
                            {{-- Skeleton loader --}}
                            <div id="notification-skeleton">
                                @for($i = 0; $i < 3; $i++)
                                <div class="px-4 py-3 flex gap-3 animate-pulse">
                                    <div class="flex-1 space-y-1.5">
                                        <div class="h-3 bg-zinc-100 rounded w-3/4"></div>
                                        <div class="h-2.5 bg-zinc-100 rounded w-1/2"></div>
                                    </div>
                                </div>
                                @endfor
                            </div>
                        </div>
                        @if(auth()->user()->isAdmin())
                        <div class="px-4 py-2 border-t border-zinc-100">
                            <a href="{{ route('logs') }}" class="text-xs text-zinc-500 hover:text-zinc-700">View all activity</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main id="main-content" class="flex-1 overflow-y-auto p-4 lg:p-6">
            @yield('content')
        </main>
    </div>
</div>

{{-- Toast container --}}
<div id="toast-container" class="fixed top-4 right-4 left-4 sm:left-auto z-[70] flex flex-col items-end gap-1 pointer-events-none" role="status" aria-live="polite"></div>

{{-- Logout form --}}
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/modal.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
@auth
<script src="https://cdn.socket.io/4.8.1/socket.io.min.js"></script>
<script src="{{ asset('js/chat.js') }}"></script>
@endauth
<script>
$(function() {
    // Sidebar toggle
    $('#sidebar-toggle').on('click', function() {
        $('#sidebar').toggleClass('-translate-x-full');
        $('#sidebar-overlay').toggleClass('hidden');
        $(this).attr('aria-expanded', !$('#sidebar').hasClass('-translate-x-full'));
    });
    function closeSidebar() {
        $('#sidebar').addClass('-translate-x-full');
        $('#sidebar-overlay').addClass('hidden');
        $('#sidebar-toggle').attr('aria-expanded', 'false');
    }
    $('#sidebar-overlay').on('click', closeSidebar);
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$('#sidebar-overlay').hasClass('hidden')) {
            closeSidebar();
            $('#sidebar-toggle').trigger('focus');
        }
    });

    // Logout: staff return to the staff portal, clients to the client login.
    $('#logout-btn').on('click', function() {
        var roles = (window.__USER__ && window.__USER__.roles) || [];
        var isClient = roles.indexOf('client') !== -1;
        api.post('/api/logout').always(function() {
            window.location.href = isClient ? '/login' : '/admin/login';
        });
    });

    // Notification bell toggle
    $('#notification-bell').on('click', function(e) {
        e.stopPropagation();
        $('#notification-dropdown').toggleClass('hidden');
        $(this).attr('aria-expanded', !$('#notification-dropdown').hasClass('hidden'));
        if (!$('#notification-dropdown').hasClass('hidden')) {
            loadNotifications();
        }
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#notification-container').length) {
            $('#notification-dropdown').addClass('hidden');
            $('#notification-bell').attr('aria-expanded', 'false');
        }
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$('#notification-dropdown').hasClass('hidden')) {
            $('#notification-dropdown').addClass('hidden');
            $('#notification-bell').attr('aria-expanded', 'false').trigger('focus');
        }
    });

    // Mark all read
    $('#mark-all-read').on('click', function(e) {
        e.preventDefault();
        api.patch('/api/notifications/read-all').done(function() {
            updateBadge(0);
            loadNotifications();
        });
    });
});

function loadNotifications() {
    $('#notification-skeleton').show();
    api.get('/api/notifications').done(function(res) {
        $('#notification-skeleton').hide();
        renderNotifications(res.data || []);
    });
}

function renderNotifications(notifications) {
    var $list = $('#notification-list');
    $list.find('.notif-item').remove();
    if (!notifications.length) {
        $list.append('<div class="notif-item flex flex-col items-center justify-center py-8 px-4 text-center"><svg class="w-8 h-8 text-zinc-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg><p class="text-sm font-semibold text-zinc-700">You\'re all caught up</p></div>');
        return;
    }
    notifications.forEach(function(n) {
        var isUnread = !n.read_at;
        var $item = $(isUnread
            ? '<button type="button" class="notif-item block w-full text-left px-4 py-3 hover:bg-zinc-50 cursor-pointer border-b border-zinc-50 last:border-0 bg-indigo-50"></button>'
            : '<div class="notif-item px-4 py-3 border-b border-zinc-50 last:border-0"></div>');
        $item.html('<p class="text-xs ' + (isUnread ? 'font-semibold text-zinc-900' : 'font-medium text-zinc-700') + ' leading-snug">' + esc(n.title) + '</p><p class="text-xs text-zinc-400 mt-0.5">' + esc(n.message) + '</p>');
        if (isUnread) {
            $item.on('click', function() { markRead(n.id); });
        }
        $list.append($item);
    });
    var unread = notifications.filter(function(n) { return !n.read_at; }).length;
    updateBadge(unread);
}

function markRead(id) {
    api.patch('/api/notifications/' + id + '/read').done(function() {
        loadNotifications();
    });
}

function updateBadge(count) {
    var $badge = $('#notification-badge');
    if (count > 0) {
        $badge.text(count > 99 ? '99+' : count).removeClass('hidden').addClass('flex');
    } else {
        $badge.addClass('hidden').removeClass('flex');
    }
}
window.updateBadge = updateBadge;
window.renderNotifications = renderNotifications;
</script>
@auth
<script src="{{ asset('js/socket.js') }}"></script>
@endauth
@stack('scripts')
</body>
</html>

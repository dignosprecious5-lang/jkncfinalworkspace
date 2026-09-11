
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Ordo Deals</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            background: #f8fafc;
            font-family: Arial, sans-serif;
            color: #172033;
            overflow-x: hidden;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 55px;
            height: 100vh;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            z-index: 200;
        }

        .sidebar-item {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 20px;
            cursor: pointer;
            transition: background .15s ease, color .15s ease;
        }

        .sidebar-item:hover {
            background: #f8fafc;
            color: #172033;
        }

        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            position: fixed;
            left: 55px;
            top: 0;
            right: 0;
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;

            display: flex;
            align-items: center;

            padding: 0 20px;

            z-index: 150;

            min-width: 0;
        }

        .logo {
            flex: 0 0 auto;
            width: 180px;

            font-weight: 700;
            line-height: 15px;
            color: #172033;
        }

        .logo-subtitle {
            font-size: 12px;
        }

        /* SEARCH */

        .global-search {
            flex: 1 1 auto;

            width: 100%;
            max-width: 420px;

            height: 38px;

            margin: 0 auto;

            border: none;
            outline: none;

            border-radius: 20px;

            background: #f1f5f9;

            padding: 0 20px;

            font-size: 15px;
            color: #334155;
        }

        .global-search::placeholder {
            color: #64748b;
        }

        /* PROFILE */

        .profile {
            flex: 0 0 auto;

            width: 180px;

            display: flex;
            align-items: center;
            justify-content: flex-end;

            gap: 15px;
        }

        /* =========================
           NOTIFICATIONS
        ========================= */

        .notification-menu {
            position: relative;
            display: none;
        }

        .notification-trigger {
            position: relative;

            border: 0;
            background: transparent;

            color: #64748b;

            padding: 4px;

            font-size: 18px;

            cursor: pointer;
        }

        .notification-badge {
            position: absolute;

            top: -3px;
            right: -5px;

            min-width: 15px;
            height: 15px;

            padding: 0 4px;

            border-radius: 10px;

            background: #ef4444;
            color: #ffffff;

            font-size: 9px;
            line-height: 15px;

            text-align: center;
        }

        .notification-dropdown {
            position: absolute;

            top: 42px;
            right: -12px;

            width: 360px;
            max-width: calc(100vw - 32px);

            background: #ffffff;

            border: 1px solid #dce5f0;
            border-radius: 10px;

            box-shadow: 0 8px 24px rgba(15, 23, 42, .14);

            z-index: 300;

            display: none;

            overflow: hidden;
        }

        .notification-dropdown.is-open {
            display: block;
        }

        .notification-dropdown-header {
            padding: 15px 16px 12px;

            border-bottom: 1px solid #e5e7eb;

            background: #ffffff;
        }

        .notification-dropdown-title-row {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 10px;
        }

        .notification-dropdown-title {
            color: #172033;

            font-size: 15px;
            font-weight: 700;
        }

        .notification-mark-all {
            border: 0;

            background: transparent;

            color: #2563eb;

            padding: 0;

            font-size: 11px;

            cursor: pointer;
        }

        .notification-count {
            margin-top: 5px;

            color: #64748b;

            font-size: 11px;
        }

        .notification-list {
            max-height: 390px;

            overflow-y: auto;
            overflow-x: hidden;
        }

        .notification-item {
            display: flex;

            gap: 10px;

            padding: 13px 15px;

            border-bottom: 1px solid #edf1f5;

            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #fbfdff;
        }

        .notification-icon {
            width: 28px;
            height: 28px;

            min-width: 28px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #e8f0ff;

            color: #2563eb;

            font-size: 12px;
        }

        .notification-content {
            min-width: 0;

            flex: 1;
        }

        .notification-title-row {
            display: flex;

            align-items: center;

            gap: 6px;
        }

        .notification-title {
            color: #172033;

            font-size: 12px;

            font-weight: 700;

            overflow-wrap: anywhere;
        }

        .notification-unread-dot {
            width: 6px;
            height: 6px;

            min-width: 6px;

            border-radius: 50%;

            background: #2563eb;
        }

        .notification-description {
            margin-top: 4px;

            color: #64748b;

            font-size: 11px;

            line-height: 1.4;
        }

        .notification-meta {
            display: flex;

            gap: 5px;

            align-items: center;

            margin-top: 7px;

            color: #94a3b8;

            font-size: 10px;
        }

        .notification-open {
            color: #2563eb;

            text-decoration: none;

            font-weight: 600;
        }

        .notification-empty {
            padding: 35px 20px;

            text-align: center;
        }

        .notification-empty strong {
            display: block;

            color: #334155;

            font-size: 13px;
        }

        .notification-empty span {
            display: block;

            margin-top: 5px;

            color: #94a3b8;

            font-size: 11px;
        }

        /* =========================
           AVATAR
        ========================= */

        .avatar {
            width: 35px;
            height: 35px;

            flex: 0 0 35px;

            background: #e2e8f0;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            color: #172033;

            font-size: 15px;

            font-weight: 500;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 55px;

            padding-top: 60px;

            min-height: 100vh;

            width: calc(100% - 55px);

            overflow-x: hidden;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .logo {
                width: 140px;
            }

            .profile {
                width: 120px;
            }

            .global-search {
                max-width: 300px;
            }
        }

        @media (max-width: 700px) {

            .topbar {
                padding: 0 15px;
            }

            .logo {
                width: auto;
            }

            .global-search {
                display: none;
            }

            .profile {
                width: auto;
            }
        }
    </style>
</head>

<body>

    {{-- =========================
         LEFT SIDEBAR
    ========================= --}}

    <aside class="sidebar">

        <div class="sidebar-item">⌂</div>
        <div class="sidebar-item">♙</div>
        <div class="sidebar-item">⌁</div>
        <div class="sidebar-item">▤</div>
        <div class="sidebar-item">▣</div>
        <div class="sidebar-item">⚙</div>

    </aside>


    {{-- =========================
         TOP HEADER
    ========================= --}}

    <header class="topbar">

        <div class="logo">

            John Kelly

            <br>

            <span class="logo-subtitle">
                &amp; Company
            </span>

        </div>


        <input
            type="text"
            class="global-search"
            placeholder="Search"
        >


        <div class="profile">

            <div class="notification-menu">

                <button
                    type="button"
                    class="notification-trigger"
                    id="notification-trigger"
                    aria-label="Notifications"
                    aria-expanded="false"
                >

                    🔔

                    @php

                        $headerNotifications = collect();
                        $headerUnreadCount = 0;

                    @endphp

                    @auth

                        @php

                            $headerNotifications = auth()
                                ->user()
                                ->notifications()
                                ->latest()
                                ->limit(50)
                                ->get();

                            $headerUnreadCount = auth()
                                ->user()
                                ->unreadNotifications()
                                ->count();

                        @endphp

                    @endauth


                    @if($headerUnreadCount > 0)

                        <span
                            class="notification-badge"
                            id="notification-badge"
                        >
                            {{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}
                        </span>

                    @endif

                </button>


                <div
                    class="notification-dropdown"
                    id="notification-dropdown"
                >

                    <div class="notification-dropdown-header">

                        <div class="notification-dropdown-title-row">

                            <span class="notification-dropdown-title">
                                Notifications
                            </span>

                            <button
                                type="button"
                                class="notification-mark-all"
                                id="notification-mark-all"
                            >
                                Mark all read
                            </button>

                        </div>


                        <div class="notification-count">

                            <span id="notification-count">
                                {{ $headerUnreadCount }}
                            </span>

                            unread notification(s)

                        </div>

                    </div>


                    <div
                        class="notification-list"
                        id="notification-list"
                    >

                        @forelse($headerNotifications as $notification)

                            @php

                                $notificationData =
                                    is_array($notification->data)
                                        ? $notification->data
                                        : [];

                                $notificationTitle =
                                    $notificationData['title']
                                    ?? $notificationData['subject']
                                    ?? class_basename($notification->type);

                                $notificationDescription =
                                    $notificationData['description']
                                    ?? $notificationData['message']
                                    ?? '';

                                $notificationSource =
                                    $notificationData['source']
                                    ?? $notificationData['type']
                                    ?? class_basename($notification->type);

                                $notificationUrl =
                                    $notificationData['url']
                                    ?? $notificationData['action_url']
                                    ?? null;

                                if (
                                    !$notificationUrl &&
                                    !empty($notificationData['deal_id'])
                                ) {

                                    $notificationUrl =
                                        route(
                                            'deals.show',
                                            $notificationData['deal_id']
                                        );

                                }

                                $notificationIcon =
                                    $notificationData['icon']
                                    ?? '•';

                            @endphp


                            <div
                                class="notification-item {{ $notification->read_at ? '' : 'unread' }}"
                                data-notification-id="{{ $notification->id }}"
                                data-notification-url="{{ $notificationUrl ?: '' }}"
                            >

                                <div class="notification-icon">
                                    {{ $notificationIcon }}
                                </div>


                                <div class="notification-content">

                                    <div class="notification-title-row">

                                        <span class="notification-title">
                                            {{ $notificationTitle }}
                                        </span>

                                        @if(!$notification->read_at)

                                            <span class="notification-unread-dot"></span>

                                        @endif

                                    </div>


                                    @if($notificationDescription)

                                        <div class="notification-description">
                                            {{ $notificationDescription }}
                                        </div>

                                    @endif


                                    <div class="notification-meta">

                                        <span>
                                            {{ $notificationSource }}
                                        </span>

                                        <span>•</span>

                                        <span>
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>

                                        <span>•</span>


                                        @if($notificationUrl)

                                            <a
                                                class="notification-open"
                                                href="{{ $notificationUrl }}"
                                            >
                                                Open
                                            </a>

                                        @else

                                            <span>
                                                Open unavailable
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="notification-empty">

                                <strong>
                                    No notifications
                                </strong>

                                <span>
                                    You’re all caught up.
                                </span>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>


            @include('components.notifications')

            <div class="avatar">
                P
            </div>

        </div>

    </header>


    {{-- =========================
         PAGE CONTENT
    ========================= --}}

    <main class="main-content">

        @yield('content')

    </main>


    {{-- =========================
         JAVASCRIPT
    ========================= --}}

    <script>

        (function () {

            var trigger =
                document.getElementById('notification-trigger');

            var dropdown =
                document.getElementById('notification-dropdown');

            var markAll =
                document.getElementById('notification-mark-all');

            var count =
                document.getElementById('notification-count');

            var badge =
                document.getElementById('notification-badge');

            var csrf =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );


            if (!trigger || !dropdown || !csrf) {
                return;
            }


            function updateCount(value) {

                value = Number(value) || 0;


                if (count) {
                    count.textContent = value;
                }


                if (value > 0) {

                    if (!badge) {

                        badge =
                            document.createElement('span');

                        badge.id =
                            'notification-badge';

                        badge.className =
                            'notification-badge';

                        trigger.appendChild(badge);

                    }


                    badge.textContent =
                        value > 99 ? '99+' : value;

                } else {

                    if (badge) {

                        badge.remove();

                        badge = null;

                    }

                }

            }


            function markRead(item) {

                return fetch(

                    '/notifications/' +
                    encodeURIComponent(
                        item.dataset.notificationId
                    ) +
                    '/read',

                    {
                        method: 'POST',

                        headers: {
                            'X-CSRF-TOKEN': csrf.content,
                            'Accept': 'application/json'
                        }
                    }

                )

                .then(function (response) {

                    if (!response.ok) {

                        throw new Error(
                            'Unable to mark notification as read'
                        );

                    }

                    return response.json();

                })

                .then(function (data) {

                    item.classList.remove('unread');


                    var dot =
                        item.querySelector(
                            '.notification-unread-dot'
                        );


                    if (dot) {
                        dot.remove();
                    }


                    updateCount(
                        data.unread_count
                    );

                });

            }


            trigger.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();


                    var open =
                        dropdown.classList.toggle(
                            'is-open'
                        );


                    trigger.setAttribute(
                        'aria-expanded',
                        open ? 'true' : 'false'
                    );

                }
            );


            document.addEventListener(
                'click',
                function (event) {

                    if (
                        !event.target.closest(
                            '.notification-menu'
                        )
                    ) {

                        dropdown.classList.remove(
                            'is-open'
                        );

                        trigger.setAttribute(
                            'aria-expanded',
                            'false'
                        );

                    }

                }
            );


            dropdown
                .querySelectorAll('.notification-item')
                .forEach(function (item) {

                    item.addEventListener(
                        'click',
                        function (event) {

                            var url =
                                item.dataset.notificationUrl;


                            if (
                                event.target.closest('a')
                            ) {

                                event.preventDefault();

                            }


                            var action =
                                item.classList.contains('unread')
                                    ? markRead(item)
                                    : Promise.resolve();


                            action.then(function () {

                                if (url) {

                                    window.location.href = url;

                                }

                            });

                        }
                    );

                });


            if (markAll) {

                markAll.addEventListener(
                    'click',
                    function (event) {

                        event.stopPropagation();


                        fetch(
                            '/notifications/read-all',
                            {
                                method: 'POST',

                                headers: {
                                    'X-CSRF-TOKEN': csrf.content,
                                    'Accept': 'application/json'
                                }
                            }
                        )

                        .then(function (response) {

                            if (!response.ok) {

                                throw new Error(
                                    'Unable to mark notifications as read'
                                );

                            }

                            return response.json();

                        })

                        .then(function (data) {

                            dropdown
                                .querySelectorAll(
                                    '.notification-item'
                                )
                                .forEach(function (item) {

                                    item.classList.remove(
                                        'unread'
                                    );


                                    var dot =
                                        item.querySelector(
                                            '.notification-unread-dot'
                                        );


                                    if (dot) {
                                        dot.remove();
                                    }

                                });


                            updateCount(
                                data.unread_count
                            );

                        });

                    }
                );

            }

        }());

    </script>

</body>

</html>
```

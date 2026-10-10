
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ORDO System') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col">

    <!-- TOP HEADER -->
    <header class="bg-white border-b border-slate-200 px-6 py-2.5 flex justify-between items-center text-xs sticky top-0 z-50">

        <!-- BRAND -->
        <div class="flex items-center space-x-2 w-48">
            <a href="/" class="flex flex-col font-serif text-slate-800 hover:opacity-90 leading-tight">
                <span class="text-sm font-bold tracking-tight text-slate-900">
                    {{ config('app.name', 'ORDO') }}
                </span>
                <span class="text-xs font-semibold text-slate-900 font-serif -mt-0.5">
                    <span class="text-blue-600 font-bold italic font-sans">&amp;</span>
                    {{ config('app.company_name', '') }}
                </span>
            </a>
        </div>

        @if (!request()->routeIs('products.workspace'))

            <!-- SEARCH -->
            <div class="w-1/3 max-w-md">
                <form
                    action="{{ Route::has('products.index') ? route('products.index') : url('/products') }}"
                    method="GET"
                    class="relative"
                >
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-2.5 text-slate-400 text-xs"></i>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ request()->routeIs('services.*') ? 'Search services...' : 'Search products...' }}"
                        class="w-full bg-slate-100/80 hover:bg-slate-100 border border-transparent focus:border-slate-300 focus:bg-white text-slate-700 pl-9 pr-4 py-1.5 rounded-full text-xs outline-none transition"
                    >
                </form>
            </div>

            <!-- HEADER ACTIONS -->
            <div class="flex items-center space-x-4">

                @auth
                    @php
                        $headerUser = auth()->user();

                        $headerUnreadCount = $headerUser
                            ->unreadNotifications()
                            ->count();

                        $headerNotifications = $headerUser
                            ->notifications()
                            ->latest()
                            ->take(10)
                            ->get();
                    @endphp

                    <!-- NOTIFICATION BELL -->
                    <div class="relative inline-block text-left">

                        <button
                            type="button"
                            id="notifBellButton"
                            onclick="toggleHeaderNotificationMenu(event)"
                            aria-label="Notifications"
                            aria-expanded="false"
                            aria-controls="headerNotificationDropdown"
                            class="relative p-1.5 text-slate-500 hover:text-slate-700 focus:outline-none transition cursor-pointer select-none"
                        >
                            <i class="fa-regular fa-bell text-base pointer-events-none"></i>

                            @if ($headerUnreadCount > 0)
                                <span class="absolute -top-0.5 -right-1 flex min-w-4 h-4 px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow-sm pointer-events-none">
                                    {{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}
                                </span>
                            @endif
                        </button>

                        <!-- NOTIFICATION DROPDOWN -->
                        <div
                            id="headerNotificationDropdown"
                            class="hidden origin-top-right absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-lg shadow-xl z-50 text-xs text-slate-700 divide-y divide-slate-100"
                        >
                            <div class="p-3 font-bold text-slate-800 flex justify-between items-center bg-slate-50 rounded-t-lg">
                                <span>Notifications</span>

                                <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold">
                                    {{ $headerUnreadCount }} Unread
                                </span>
                            </div>

                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">

                                @forelse ($headerNotifications as $notification)
                                    @php
                                        $notificationData = $notification->data ?? [];

                                        $notificationTitle = data_get(
                                            $notificationData,
                                            'title',
                                            data_get(
                                                $notificationData,
                                                'service_name',
                                                data_get(
                                                    $notificationData,
                                                    'product_name',
                                                    class_basename($notification->type)
                                                )
                                            )
                                        );

                                        $notificationMessage = data_get(
                                            $notificationData,
                                            'message',
                                            data_get(
                                                $notificationData,
                                                'description',
                                                ''
                                            )
                                        );

                                        $notificationUrl = data_get(
                                            $notificationData,
                                            'url'
                                        );
                                    @endphp

                                    <div
                                        class="p-3 hover:bg-blue-50/50 transition {{ $notification->read_at ? 'opacity-70' : '' }}"
                                        data-notification-row="{{ $notification->id }}"
                                    >
                                        <div class="font-semibold text-slate-800 flex items-center justify-between gap-2">
                                            <span>
                                                {{ $notificationTitle }}
                                            </span>

                                            @if (!$notification->read_at)
                                                <span
                                                    class="h-2 w-2 rounded-full bg-blue-600 shrink-0"
                                                    title="Unread"
                                                ></span>
                                            @endif
                                        </div>

                                        @if ($notificationMessage !== '')
                                            <div class="text-[11px] text-slate-500 mt-0.5">
                                                {{ $notificationMessage }}
                                            </div>
                                        @endif

                                        <div class="flex justify-between items-center mt-2 gap-2">
                                            <span class="text-[10px] text-slate-400">
                                                {{ $notification->created_at?->diffForHumans() }}
                                            </span>

                                            <div class="flex items-center gap-3">
                                                @if (!$notification->read_at)
                                                    <button
                                                        type="button"
                                                        onclick="markHeaderNotificationRead(event, '{{ $notification->id }}')"
                                                        class="text-[10px] font-semibold text-blue-600 hover:underline"
                                                    >
                                                        Mark read
                                                    </button>
                                                @endif

                                                @if (is_string($notificationUrl) && $notificationUrl !== '')
                                                    <a
                                                        href="{{ $notificationUrl }}"
                                                        class="text-[10px] font-semibold text-slate-600 hover:text-blue-600 hover:underline"
                                                    >
                                                        View
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                @empty
                                    <div class="p-6 text-center text-slate-400">
                                        <i class="fa-regular fa-bell-slash text-xl mb-2"></i>
                                        <p>No notifications yet.</p>
                                    </div>
                                @endforelse

                            </div>

                            <div class="p-2 text-center bg-slate-50 rounded-b-lg">
                                @if ($headerUnreadCount > 0)
                                    <button
                                        type="button"
                                        onclick="markAllHeaderNotificationsRead(event)"
                                        class="text-blue-600 hover:underline text-[11px] font-semibold block w-full py-1"
                                    >
                                        Mark All as Read
                                    </button>
                                @else
                                    <span class="text-[11px] text-slate-400 block py-1">
                                        You're all caught up
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endauth

                <!-- USER PROFILE -->
                <div class="relative inline-block text-left">

                    <button
                        type="button"
                        id="userAvatarButton"
                        onclick="toggleUserProfileMenu(event)"
                        aria-label="User profile"
                        aria-expanded="false"
                        aria-controls="userProfileDropdown"
                        class="w-7 h-7 bg-blue-100 border border-blue-200 rounded-full flex items-center justify-center text-xs font-bold text-blue-800 shadow-sm cursor-pointer hover:bg-blue-200 transition focus:outline-none select-none"
                    >
                        @auth
                            {{ strtoupper(substr(auth()->user()->name ?? '', 0, 1)) }}
                        @else
                            <i class="fa-regular fa-user"></i>
                        @endauth
                    </button>

                    <div
                        id="userProfileDropdown"
                        class="hidden origin-top-right absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-xl z-50 text-xs text-slate-700 divide-y divide-slate-100 py-1"
                    >
                        @auth
                            <div class="px-4 py-3">
                                <p class="font-bold text-slate-900 text-sm truncate">
                                    {{ auth()->user()->name }}
                                </p>

                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                                    {{ auth()->user()->role ?? auth()->user()->email }}
                                </p>
                            </div>

                            <div class="py-1">
                                @if (Route::has('profile.edit'))
                                    <a
                                        href="{{ route('profile.edit') }}"
                                        class="w-full text-left px-4 py-2 hover:bg-slate-50 flex items-center space-x-3 text-slate-700 transition"
                                    >
                                        <i class="fa-solid fa-key w-4 text-center"></i>
                                        <span>Change Password</span>
                                    </a>
                                @endif
                            </div>

                            <div class="py-1">
                                @if (Route::has('logout'))
                                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                                        @csrf

                                        <button
                                            type="submit"
                                            class="w-full text-left px-4 py-2 hover:bg-rose-50 flex items-center space-x-3 text-rose-600 font-semibold transition cursor-pointer"
                                        >
                                            <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                                            <span>Logout</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @else
                            @if (Route::has('login'))
                                <a
                                    href="{{ route('login') }}"
                                    class="block px-4 py-2 hover:bg-slate-50"
                                >
                                    Login
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        @endif
    </header>

    <!-- MAIN CONTAINER -->
    <div class="flex flex-1 min-h-[calc(100vh-53px)]">

        <!-- LEFT SIDEBAR -->
        <aside class="w-56 bg-white border-r border-slate-200 p-4 shrink-0 flex flex-col justify-between text-xs sticky top-[53px] h-[calc(100vh-53px)] z-[999] relative pointer-events-auto">

            <div class="space-y-5 overflow-y-auto custom-scrollbar">

                <!-- BRANDING -->
                <div class="px-2 pb-2 border-b border-slate-100">
                    <a href="/" class="flex flex-col font-serif text-slate-800 hover:opacity-90 leading-tight">
                        <span class="text-sm font-bold tracking-tight text-slate-900">
                            {{ config('app.name', 'ORDO') }}
                        </span>
                        <span class="text-xs font-semibold text-slate-900 font-serif -mt-0.5">
                            {{ config('app.company_name', '') }}
                        </span>
                    </a>
                </div>

                <div class="flex items-center justify-between px-2">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-layer-group text-blue-600 text-sm"></i>
                        <div>
                            <h2 class="font-bold text-slate-800 text-xs">
                                Enterprise Menu
                            </h2>
                            <span class="text-[10px] text-slate-400 block -mt-0.5">
                                Navigation
                            </span>
                        </div>
                    </div>
                    <i class="fa-solid fa-angles-right text-slate-300 text-[10px]"></i>
                </div>

                <nav class="space-y-1 text-slate-600">

                    <a href="#" class="flex items-center space-x-2.5 px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <i class="fa-regular fa-user text-slate-400 w-4"></i>
                        <span>Admin</span>
                    </a>

                    <a href="#" class="flex items-center space-x-2.5 px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <i class="fa-regular fa-building text-slate-400 w-4"></i>
                        <span>Town Hall</span>
                    </a>

                    <a href="#" class="flex items-center space-x-2.5 px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <i class="fa-regular fa-folder text-slate-400 w-4"></i>
                        <span>Corporate</span>
                    </a>

                    <a href="#" class="flex items-center space-x-2.5 px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <i class="fa-regular fa-file-lines text-slate-400 w-4"></i>
                        <span>Policies</span>
                    </a>

                    <a href="#" class="flex items-center space-x-2.5 px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <i class="fa-solid fa-calculator text-slate-400 w-4"></i>
                        <span>Finance</span>
                    </a>

                    <a href="#" class="flex items-center space-x-2.5 px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <i class="fa-regular fa-id-badge text-slate-400 w-4"></i>
                        <span>Human Capital</span>
                    </a>

                    <!-- MARKETING -->
                    <div class="space-y-1 pt-1">
                        <div class="w-full flex items-center justify-between px-2.5 py-2 rounded-md {{ request()->routeIs('products.*', 'services.*') ? 'bg-blue-50/70 text-blue-700 font-semibold' : 'bg-slate-50/70 text-slate-700 font-semibold' }}">
                            <div class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-bullhorn text-blue-600 w-4"></i>
                                <span>Marketing</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] text-blue-500"></i>
                        </div>

                        <div class="pl-9 space-y-1 pt-0.5">
                            <a
                                href="{{ Route::has('products.index') ? route('products.index') : url('/products') }}"
                                class="block py-1 {{ request()->routeIs('products.*') ? 'text-blue-600 font-bold' : 'text-slate-600 hover:text-blue-600' }}"
                            >
                                Product
                            </a>

                            <a
                                href="{{ Route::has('services.index') ? route('services.index') : url('/services') }}"
                                class="block py-1 {{ request()->routeIs('services.*') ? 'text-blue-600 font-bold' : 'text-slate-600 hover:text-blue-600' }}"
                            >
                                Services
                            </a>
                        </div>
                    </div>

                    <a href="#" class="flex items-center justify-between px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <div class="flex items-center space-x-2.5">
                            <i class="fa-solid fa-chart-line text-slate-400 w-4"></i>
                            <span>Sales</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
                    </a>

                    <a href="#" class="flex items-center justify-between px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <div class="flex items-center space-x-2.5">
                            <i class="fa-regular fa-credit-card text-slate-400 w-4"></i>
                            <span>Accounts</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
                    </a>

                    <a
                        href="{{ Route::has('engagements.index') ? route('engagements.index') : url('/engagements') }}"
                        class="flex items-center justify-between px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600"
                    >
                        <div class="flex items-center space-x-2.5">
                            <i class="fa-solid fa-gear text-slate-400 w-4"></i>
                            <span>Operations</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
                    </a>

                </nav>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 px-8 py-6 space-y-5 overflow-x-hidden">
            @yield('content')
        </main>
    </div>

    <!-- HEADER DROPDOWN JAVASCRIPT -->
    <script>
        function toggleHeaderNotificationMenu(event) {
            if (event) event.stopPropagation();

            const userMenu = document.getElementById('userProfileDropdown');
            const userButton = document.getElementById('userAvatarButton');
            const notifMenu = document.getElementById('headerNotificationDropdown');
            const notifButton = document.getElementById('notifBellButton');

            if (userMenu) userMenu.classList.add('hidden');

            if (userButton) {
                userButton.setAttribute('aria-expanded', 'false');
            }

            if (notifMenu) {
                notifMenu.classList.toggle('hidden');

                if (notifButton) {
                    notifButton.setAttribute(
                        'aria-expanded',
                        String(!notifMenu.classList.contains('hidden'))
                    );
                }
            }
        }

        function toggleUserProfileMenu(event) {
            if (event) event.stopPropagation();

            const notifMenu = document.getElementById('headerNotificationDropdown');
            const notifButton = document.getElementById('notifBellButton');
            const userMenu = document.getElementById('userProfileDropdown');
            const userButton = document.getElementById('userAvatarButton');

            if (notifMenu) notifMenu.classList.add('hidden');

            if (notifButton) {
                notifButton.setAttribute('aria-expanded', 'false');
            }

            if (userMenu) {
                userMenu.classList.toggle('hidden');

                if (userButton) {
                    userButton.setAttribute(
                        'aria-expanded',
                        String(!userMenu.classList.contains('hidden'))
                    );
                }
            }
        }

        async function markHeaderNotificationRead(event, notificationId) {
            if (event) event.stopPropagation();

            try {
                const response = await fetch(
                    `/notifications/${encodeURIComponent(notificationId)}/mark-read`,
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to mark notification as read.');
                }

                window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Unable to update the notification. Please try again.');
            }
        }

        async function markAllHeaderNotificationsRead(event) {
            if (event) event.stopPropagation();

            try {
                const response = await fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error('Failed to mark notifications as read.');
                }

                window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Unable to update notifications. Please try again.');
            }
        }

        document.addEventListener('click', function (event) {
            const notifMenu = document.getElementById('headerNotificationDropdown');
            const notifButton = document.getElementById('notifBellButton');

            if (
                notifMenu &&
                !notifMenu.contains(event.target) &&
                notifButton &&
                !notifButton.contains(event.target)
            ) {
                notifMenu.classList.add('hidden');
                notifButton.setAttribute('aria-expanded', 'false');
            }

            const userMenu = document.getElementById('userProfileDropdown');
            const userButton = document.getElementById('userAvatarButton');

            if (
                userMenu &&
                !userMenu.contains(event.target) &&
                userButton &&
                !userButton.contains(event.target)
            ) {
                userMenu.classList.add('hidden');
                userButton.setAttribute('aria-expanded', 'false');
            }
        });
    </script>

</body>
</html>

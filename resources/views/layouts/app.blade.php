<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORDO System</title>
    <!-- Tailwind CSS & FontAwesome Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Header Navigation Bar -->
    <header class="bg-white border-b border-slate-200 px-6 py-2.5 flex justify-between items-center text-xs sticky top-0 z-50">
        <div class="flex items-center space-x-2 w-48">
            <a href="/" class="flex flex-col font-serif text-slate-800 hover:opacity-90 leading-tight">
                <span class="text-sm font-bold tracking-tight text-slate-900">John Kelly</span>
                <span class="text-xs font-semibold text-slate-900 font-serif -mt-0.5">
                    <span class="text-blue-600 font-bold italic font-sans">&</span> Company
                </span>
            </a>
        </div>

        <div class="w-1/3 max-w-md">
            <form action="{{ Route::has('services.index') ? route('services.index') : '/services' }}" method="GET" class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-2.5 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="w-full bg-slate-100/80 hover:bg-slate-100 border border-transparent focus:border-slate-300 focus:bg-white text-slate-700 pl-9 pr-4 py-1.5 rounded-full text-xs outline-none transition">
            </form>
        </div>

        <!-- NOTIFICATION BELL & USER PROFILE SECTION -->
        <div class="flex items-center space-x-4">
            
            <!-- WORKING NOTIFICATION BELL & CLICKABLE DROPDOWN -->
            <div class="relative inline-block text-left">
                <button type="button" id="notifBellButton" onclick="toggleHeaderNotificationMenu(event)" class="relative p-1.5 text-slate-500 hover:text-slate-700 focus:outline-none transition cursor-pointer select-none">
                    <i class="fa-regular fa-bell text-base pointer-events-none"></i>
                    <!-- Red Notification Badge -->
                    <span class="absolute -top-0.5 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow-sm pointer-events-none">
                        9+
                    </span>
                </button>

                <!-- Notification Dropdown with Real Clickable Redirect Links -->
                <div id="headerNotificationDropdown" class="hidden origin-top-right absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-lg shadow-xl z-50 text-xs text-slate-700 divide-y divide-slate-100">
                    <div class="p-3 font-bold text-slate-800 flex justify-between items-center bg-slate-50 rounded-t-lg">
                        <span>Notifications</span>
                        <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold">9 New</span>
                    </div>
                    <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                        
                        <!-- Link 1: Direct to Services Pending Approval -->
                        <a href="{{ Route::has('services.index') ? route('services.index', ['status' => 'for_approval']) : '/services?status=for_approval' }}" class="block p-3 hover:bg-slate-50 transition cursor-pointer group">
                            <div class="font-semibold text-slate-800 group-hover:text-blue-600 transition">New Service Approval</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Corporate Advisory V2 is waiting for your review.</div>
                            <span class="text-[10px] text-slate-400 mt-1 block">5 mins ago</span>
                        </a>

                        <!-- Link 2: Direct to Proposals -->
                        <a href="{{ Route::has('proposals.index') ? route('proposals.index') : '/proposals' }}" class="block p-3 hover:bg-slate-50 transition cursor-pointer group">
                            <div class="font-semibold text-slate-800 group-hover:text-blue-600 transition">Proposal Accepted</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">On-site Profit & Loss Review proposal signed.</div>
                            <span class="text-[10px] text-slate-400 mt-1 block">1 hour ago</span>
                        </a>

                        <!-- Link 3: Direct to Requirements -->
                        <a href="{{ Route::has('requirements.index') ? route('requirements.index') : '/requirements' }}" class="block p-3 hover:bg-slate-50 transition cursor-pointer group">
                            <div class="font-semibold text-slate-800 group-hover:text-blue-600 transition">Requirement Submitted</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">SEC Articles of Incorporation uploaded by client.</div>
                            <span class="text-[10px] text-slate-400 mt-1 block">3 hours ago</span>
                        </a>

                    </div>
                    <div class="p-2 text-center bg-slate-50 rounded-b-lg">
                        <a href="{{ Route::has('services.index') ? route('services.index') : '/services' }}" class="text-blue-600 hover:underline text-[11px] font-semibold block w-full py-1">View All Notifications</a>
                    </div>
                </div>
            </div>

            <!-- USER AVATAR & DROPDOWN MENU -->
            <div class="relative inline-block text-left">
                <button type="button" id="userAvatarButton" onclick="toggleUserProfileMenu(event)" class="w-7 h-7 bg-blue-100 border border-blue-200 rounded-full flex items-center justify-center text-xs font-bold text-blue-800 shadow-sm cursor-pointer hover:bg-blue-200 transition focus:outline-none select-none">
                    M
                </button>

                <!-- User Dropdown Menu -->
                <div id="userProfileDropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-md shadow-lg z-50 py-1 text-slate-700">
                    <div class="px-3 py-2 border-b border-slate-100">
                        <p class="font-semibold text-slate-800">Manager Account</p>
                        <p class="text-[10px] text-slate-400 truncate">user@ordo-system.com</p>
                    </div>
                    <a href="#" class="block px-3 py-1.5 hover:bg-slate-50 text-slate-700">My Profile</a>
                    <a href="#" class="block px-3 py-1.5 hover:bg-slate-50 text-slate-700">Account Settings</a>
                    <div class="border-t border-slate-100 my-1"></div>
                    <a href="#" class="block px-3 py-1.5 hover:bg-rose-50 text-rose-600 font-semibold">Log Out</a>
                </div>
            </div>

        </div>
    </header>

    <!-- Main Container with Left Sidebar -->
    <div class="flex flex-1 min-h-[calc(100vh-53px)]">

        <!-- LEFT SIDEBAR -->
        <!-- BAGONG CODE -->
<aside class="w-56 bg-white border-r border-slate-200 p-4 shrink-0 flex flex-col justify-between text-xs sticky top-[53px] h-[calc(100vh-53px)] z-[999] relative pointer-events-auto">
            <div class="space-y-5 overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between px-2">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-layer-group text-blue-600 text-sm"></i>
                        <div>
                            <h2 class="font-bold text-slate-800 text-xs">Enterprise Menu</h2>
                            <span class="text-[10px] text-slate-400 block -mt-0.5">Navigation</span>
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

                    <!-- Marketing Dropdown Section -->
                    <div class="space-y-1 pt-1">
                        <div class="w-full flex items-center justify-between px-2.5 py-2 rounded-md bg-blue-50/70 text-blue-700 font-semibold">
                            <div class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-bullhorn text-blue-600 w-4"></i>
                                <span>Marketing</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] text-blue-500"></i>
                        </div>
                        <div class="pl-9 space-y-1 pt-0.5">
                            <a href="#" class="block py-1 text-slate-500 hover:text-slate-800">Product</a>
                            <a href="{{ Route::has('services.index') ? route('services.index') : '/services' }}" class="block py-1 text-slate-600 hover:text-blue-600">Services</a>
                            
                            <!-- REQUIREMENTS MENU LINK -->
                            <a href="{{ Route::has('requirements.index') ? route('requirements.index') : '/requirements' }}" class="block py-1 font-semibold text-blue-600 flex items-center justify-between">
                                <span>Requirements</span>
                                <span class="bg-blue-100 text-blue-700 text-[9px] font-bold px-1.5 py-0.2 rounded-full">New</span>
                            </a>
                        </div>
                    </div>

                    <!-- Proposals & Contracts Navigation -->
                    <div class="space-y-1 pt-1">
                        <a href="{{ Route::has('proposals.index') ? route('proposals.index') : '/proposals' }}" class="w-full flex items-center justify-between px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-700 font-medium transition">
                            <div class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-file-signature text-slate-400 w-4"></i>
                                <span>Proposals & Contracts</span>
                            </div>
                            <span class="bg-slate-100 text-slate-600 text-[9px] font-bold px-1.5 py-0.2 rounded-full">New</span>
                        </a>
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

                    <a href="{{ Route::has('engagements.index') ? route('engagements.index') : '/engagements' }}" class="flex items-center justify-between px-2.5 py-2 rounded-md hover:bg-slate-50 text-slate-600">
                        <div class="flex items-center space-x-2.5">
                            <i class="fa-solid fa-gear text-slate-400 w-4"></i>
                            <span>Operations</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="flex-1 px-8 py-6 space-y-5 overflow-x-hidden">
            @yield('content')
        </main>
    </div>

    <!-- JAVASCRIPT HANDLERS FOR HEADER DROPDOWNS -->
    <script>
        function toggleHeaderNotificationMenu(event) {
            if (event) {
                event.stopPropagation();
            }
            const userMenu = document.getElementById('userProfileDropdown');
            if (userMenu) userMenu.classList.add('hidden');

            const notifMenu = document.getElementById('headerNotificationDropdown');
            if (notifMenu) {
                notifMenu.classList.toggle('hidden');
            }
        }

        function toggleUserProfileMenu(event) {
            if (event) {
                event.stopPropagation();
            }
            const notifMenu = document.getElementById('headerNotificationDropdown');
            if (notifMenu) notifMenu.classList.add('hidden');

            const userMenu = document.getElementById('userProfileDropdown');
            if (userMenu) {
                userMenu.classList.toggle('hidden');
            }
        }

        // Close Dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const notifMenu = document.getElementById('headerNotificationDropdown');
            const notifBtn = document.getElementById('notifBellButton');
            
            if (notifMenu && !notifMenu.contains(e.target) && !notifBtn.contains(e.target)) {
                notifMenu.classList.add('hidden');
            }

            const userMenu = document.getElementById('userProfileDropdown');
            const userBtn = document.getElementById('userAvatarButton');
            
            if (userMenu && !userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
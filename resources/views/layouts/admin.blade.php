<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ApexPOS Platform Control Center')</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="h-16 bg-slate-900 border-b border-slate-800 px-6 flex items-center justify-between sticky top-0 z-40">
        <div className="flex items-center space-x-3">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3">
                <div class="w-9 h-9 bg-emerald-500/10 border border-emerald-500/30 rounded-lg flex items-center justify-center text-emerald-400 font-black text-lg">
                    A
                </div>
                <div>
                    <h1 class="font-extrabold text-base tracking-tight text-white flex items-center space-x-2">
                        <span>ApexPOS Cloud Engine</span>
                        <span class="px-2 py-0.5 text-[10px] uppercase font-bold bg-emerald-500/20 text-emerald-400 rounded-full border border-emerald-500/30">SuperAdmin</span>
                    </h1>
                    <p class="text-[11px] text-slate-400">Platform Tenant & Licensing Management</p>
                </div>
            </a>
        </div>

        <div class="flex items-center space-x-6">
            <nav class="hidden md:flex items-center space-x-4 text-xs font-bold">
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.tenants') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.tenants*') ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-white' }}">
                    Client Tenants
                </a>
                <a href="{{ route('admin.licenses') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.licenses*') ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-white' }}">
                    License Keys
                </a>
                <a href="{{ route('admin.sync-logs') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.sync-logs*') ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-white' }}">
                    Sync Audit Logs
                </a>
            </nav>

            <div class="flex items-center space-x-3 pl-4 border-l border-slate-800">
                <span class="text-xs text-slate-300 font-medium">{{ Auth::guard('super_admin')->user()->name ?? 'SuperAdmin' }}</span>
                <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 rounded-lg transition-colors cursor-pointer">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Success & Error Alert Messages -->
    @if(session('success'))
    <div class="bg-emerald-950/80 border-b border-emerald-800 px-6 py-3 text-xs text-emerald-300 flex items-center space-x-2">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Main Content Area -->
    <main class="flex-1 p-6 md:p-8 max-w-7xl w-full mx-auto">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 px-6 py-4 text-center text-xs text-slate-500">
        ApexPOS Multi-Tenant SaaS Control Center &copy; {{ date('Y') }} ApexPOS Cloud Engine v1.0.0
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

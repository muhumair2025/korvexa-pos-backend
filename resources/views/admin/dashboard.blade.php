@extends('layouts.admin')

@section('title', 'Platform Overview — ApexPOS')

@section('content')
<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Platform Control Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Multi-tenant client monitoring, active licensing overview, and sync activity stream.</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.licenses') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-lg shadow transition-all flex items-center space-x-2">
                <i data-lucide="key" class="w-4 h-4"></i>
                <span>Generate License Key</span>
            </a>
        </div>
    </div>

    <!-- 4 Stats Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-slate-900 border border-slate-800 rounded-xl space-y-2">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider">Total Client Marts</span>
                <i data-lucide="building-2" class="w-5 h-5 text-emerald-400"></i>
            </div>
            <p class="text-3xl font-black text-white">{{ number_format($stats['total_tenants']) }}</p>
            <p class="text-[11px] text-emerald-400 font-semibold">{{ $stats['active_tenants'] }} active accounts</p>
        </div>

        <div class="p-5 bg-slate-900 border border-slate-800 rounded-xl space-y-2">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider">Active Subscriptions</span>
                <i data-lucide="shield-check" class="w-5 h-5 text-blue-400"></i>
            </div>
            <p class="text-3xl font-black text-white">{{ number_format($stats['active_licenses']) }}</p>
            <p class="text-[11px] text-rose-400 font-semibold">{{ $stats['expired_licenses'] }} expired licenses</p>
        </div>

        <div class="p-5 bg-slate-900 border border-slate-800 rounded-xl space-y-2">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider">Registered Terminals/Users</span>
                <i data-lucide="users" class="w-5 h-5 text-purple-400"></i>
            </div>
            <p class="text-3xl font-black text-white">{{ number_format($stats['total_users']) }}</p>
            <p class="text-[11px] text-slate-400 font-semibold">Across all client mart counters</p>
        </div>

        <div class="p-5 bg-slate-900 border border-slate-800 rounded-xl space-y-2">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider">Total Sync Events</span>
                <i data-lucide="refresh-cw" class="w-5 h-5 text-amber-400"></i>
            </div>
            <p class="text-3xl font-black text-white">{{ number_format($stats['total_sync_logs']) }}</p>
            <p class="text-[11px] text-amber-400 font-semibold">Push / Pull cloud events</p>
        </div>
    </div>

    <!-- Recent Tenants & Sync Stream -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Client Tenants Table (2 Cols) -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-sm font-extrabold text-white flex items-center space-x-2">
                    <i data-lucide="store" class="w-4 h-4 text-emerald-400"></i>
                    <span>Recent Client Marts</span>
                </h2>
                <a href="{{ route('admin.tenants') }}" class="text-xs text-emerald-400 hover:underline font-bold">View All →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase bg-slate-950/60 border-b border-slate-800 font-bold">
                        <tr>
                            <th class="p-3">Mart Name</th>
                            <th class="p-3">Owner Details</th>
                            <th class="p-3">License Key</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300 font-medium">
                        @forelse($recentTenants as $tenant)
                        <tr class="hover:bg-slate-800/40">
                            <td class="p-3 font-bold text-white">{{ $tenant->business_name }}</td>
                            <td class="p-3">
                                <div>{{ $tenant->owner_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ $tenant->owner_email }}</div>
                            </td>
                            <td class="p-3 font-mono font-bold text-emerald-400">
                                {{ $tenant->licenses->first()?->license_key ?? 'N/A' }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $tenant->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30' }}">
                                    {{ strtoupper($tenant->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <form action="{{ route('admin.tenants.toggle-status', $tenant->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold {{ $tenant->status === 'active' ? 'text-rose-400 hover:underline' : 'text-emerald-400 hover:underline' }}">
                                        {{ $tenant->status === 'active' ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-slate-500">No client marts registered yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Live Sync Stream (1 Col) -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-sm font-extrabold text-white flex items-center space-x-2">
                    <i data-lucide="activity" class="w-4 h-4 text-amber-400"></i>
                    <span>Live Sync Activity</span>
                </h2>
                <a href="{{ route('admin.sync-logs') }}" class="text-xs text-emerald-400 hover:underline font-bold">Logs →</a>
            </div>

            <div class="space-y-3">
                @forelse($recentSyncs as $log)
                <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-lg text-xs space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-white">{{ $log->tenant->business_name ?? 'Client Mart #' . $log->tenant_id }}</span>
                        <span class="px-1.5 py-0.5 text-[10px] uppercase font-bold rounded {{ $log->direction === 'push' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400' }}">
                            {{ $log->direction }}
                        </span>
                    </div>
                    <div class="text-slate-400 text-[11px] flex justify-between">
                        <span>Terminal: {{ $log->device_id }}</span>
                        <span class="font-mono text-slate-500">{{ $log->synced_at ? $log->synced_at->diffForHumans() : '' }}</span>
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-slate-500 text-xs">No sync logs recorded yet.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection

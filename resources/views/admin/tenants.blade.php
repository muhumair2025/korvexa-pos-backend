@extends('layouts.admin')

@section('title', 'Client Tenants — ApexPOS Admin')

@section('content')
<div class="space-y-6">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Client Mart Tenants</h1>
            <p class="text-xs text-slate-400 mt-1">Manage registered business clients, view active license keys, and toggle store status.</p>
        </div>

        <form action="{{ route('admin.tenants') }}" method="GET" class="flex items-center space-x-2">
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="Search business, owner, email..." 
                class="px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-lg text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-lg transition-all cursor-pointer">
                Search
            </button>
        </form>
    </div>

    <!-- Tenants Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-xs">
            <thead class="text-slate-400 uppercase bg-slate-950/80 border-b border-slate-800 font-bold">
                <tr>
                    <th class="p-4">ID</th>
                    <th class="p-4">Business Name</th>
                    <th class="p-4">Owner Contact</th>
                    <th class="p-4">Active License Key</th>
                    <th class="p-4">Subscription Plan</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300 font-medium">
                @forelse($tenants as $tenant)
                <tr class="hover:bg-slate-800/40">
                    <td class="p-4 font-mono text-slate-500">#{{ $tenant->id }}</td>
                    <td class="p-4 font-bold text-white text-sm">{{ $tenant->business_name }}</td>
                    <td class="p-4">
                        <div class="font-bold text-slate-200">{{ $tenant->owner_name }}</div>
                        <div class="text-[11px] text-slate-400">{{ $tenant->owner_email }}</div>
                        <div class="text-[11px] text-slate-500">{{ $tenant->owner_phone ?? 'No phone' }}</div>
                    </td>
                    <td class="p-4 font-mono font-bold text-emerald-400">
                        {{ $tenant->licenses->first()?->license_key ?? 'N/A' }}
                    </td>
                    <td class="p-4 uppercase font-bold text-slate-300">
                        {{ $tenant->licenses->first()?->plan ?? 'Starter' }}
                    </td>
                    <td class="p-4">
                        <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-full border {{ $tenant->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30' }}">
                            {{ strtoupper($tenant->status) }}
                        </span>
                    </td>
                    <td class="p-4 text-right">
                        <form action="{{ route('admin.tenants.toggle-status', $tenant->id) }}" method="POST" class="inline">
                            @csrf
                            <button 
                                type="submit" 
                                onclick="return confirm('Are you sure you want to {{ $tenant->status === 'active' ? 'suspend' : 'activate' }} this tenant?')"
                                class="px-3 py-1 text-xs font-bold rounded border transition-all cursor-pointer {{ $tenant->status === 'active' ? 'bg-rose-500/10 border-rose-500/30 text-rose-400 hover:bg-rose-500/20' : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20' }}"
                            >
                                {{ $tenant->status === 'active' ? 'Suspend Account' : 'Activate Account' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-slate-500">No client marts found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($tenants->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $tenants->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

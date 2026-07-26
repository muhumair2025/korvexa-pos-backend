@extends('layouts.admin')

@section('title', 'License Keys — ApexPOS Admin')

@section('content')
<div class="space-y-6">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">License Keys & Subscriptions</h1>
            <p class="text-xs text-slate-400 mt-1">Generate new license keys for clients, extend subscriptions, and manage counter device limits.</p>
        </div>

        <button 
            onclick="document.getElementById('generateModal').classList.remove('hidden')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-lg shadow-lg transition-all flex items-center space-x-2 cursor-pointer"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Generate New License Key</span>
        </button>
    </div>

    <!-- License Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-xs">
            <thead class="text-slate-400 uppercase bg-slate-950/80 border-b border-slate-800 font-bold">
                <tr>
                    <th class="p-4">License Key</th>
                    <th class="p-4">Business / Mart</th>
                    <th class="p-4">Plan</th>
                    <th class="p-4">Active Devices</th>
                    <th class="p-4">Expires At</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300 font-medium">
                @forelse($licenses as $license)
                <tr class="hover:bg-slate-800/40">
                    <td class="p-4 font-mono font-bold text-emerald-400 text-sm tracking-wider">
                        {{ $license->license_key }}
                    </td>
                    <td class="p-4">
                        <div class="font-bold text-white">{{ $license->tenant->business_name ?? 'N/A' }}</div>
                        <div class="text-[11px] text-slate-400">{{ $license->tenant->owner_email ?? '' }}</div>
                    </td>
                    <td class="p-4 uppercase font-bold text-slate-200">
                        {{ $license->plan }}
                    </td>
                    <td class="p-4 font-bold">
                        {{ $license->active_devices }} / {{ $license->max_counters === 0 ? 'Unlimited' : $license->max_counters }}
                    </td>
                    <td class="p-4 font-mono">
                        {{ $license->expires_at ? $license->expires_at->format('M d, Y') : 'Lifetime' }}
                        @if($license->expires_at && $license->expires_at->isPast())
                            <span class="text-rose-400 font-bold text-[10px] ml-1">(Expired)</span>
                        @endif
                    </td>
                    <td class="p-4">
                        <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-full border {{ $license->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30' }}">
                            {{ strtoupper($license->status) }}
                        </span>
                    </td>
                    <td class="p-4 text-right space-x-2">
                        <!-- Extend Form -->
                        <form action="{{ route('admin.licenses.extend', $license->id) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="days" value="365">
                            <button type="submit" class="px-2.5 py-1 bg-blue-500/10 border border-blue-500/30 text-blue-400 hover:bg-blue-500/20 text-[11px] font-bold rounded cursor-pointer">
                                +1 Year
                            </button>
                        </form>

                        @if($license->status === 'active')
                        <form action="{{ route('admin.licenses.revoke', $license->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" onclick="return confirm('Revoke key {{ $license->license_key }}?')" class="px-2.5 py-1 bg-rose-500/10 border border-rose-500/30 text-rose-400 hover:bg-rose-500/20 text-[11px] font-bold rounded cursor-pointer">
                                Revoke
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-slate-500">No license keys generated yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($licenses->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $licenses->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Generate License Modal -->
<div id="generateModal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-extrabold text-white">Generate Client License Key</h3>
            <button onclick="document.getElementById('generateModal').classList.add('hidden')" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
        </div>

        <form action="{{ route('admin.licenses.create') }}" method="POST" class="space-y-3.5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-300 mb-1">Business / Mart Name</label>
                <input type="text" name="business_name" required placeholder="e.g. Al-Madina Supermarket" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-white" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Owner Name</label>
                    <input type="text" name="owner_name" required placeholder="Muhammad Ali" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Owner Email</label>
                    <input type="email" name="owner_email" required placeholder="ali@mart.com" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-white" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Plan</label>
                    <select name="plan" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-white font-bold">
                        <option value="starter">Starter</option>
                        <option value="professional">Professional</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Duration (Days)</label>
                    <input type="number" name="duration_days" value="365" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-white font-mono" />
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Max Counters</label>
                    <input type="number" name="max_counters" value="0" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-white font-mono" title="0 = Unlimited" />
                </div>
            </div>

            <div class="pt-2 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('generateModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold rounded shadow">
                    Generate Key
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

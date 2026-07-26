@extends('layouts.admin')

@section('title', 'Sync Audit Logs — ApexPOS Admin')

@section('content')
<div class="space-y-6">
    
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight">Sync Audit Logs</h1>
        <p class="text-xs text-slate-400 mt-1">Live background push/pull event history recorded from client POS counter terminals.</p>
    </div>

    <!-- Sync Logs Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-xs">
            <thead class="text-slate-400 uppercase bg-slate-950/80 border-b border-slate-800 font-bold">
                <tr>
                    <th class="p-4">Timestamp</th>
                    <th class="p-4">Client Mart</th>
                    <th class="p-4">Terminal Device ID</th>
                    <th class="p-4">Direction</th>
                    <th class="p-4">Tables Synced</th>
                    <th class="p-4">Pushed / Pulled</th>
                    <th class="p-4">Conflicts</th>
                    <th class="p-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300 font-medium">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-800/40">
                    <td class="p-4 font-mono text-slate-400">
                        {{ $log->synced_at ? $log->synced_at->format('M d, Y H:i:s') : 'N/A' }}
                    </td>
                    <td class="p-4 font-bold text-white">
                        {{ $log->tenant->business_name ?? 'Tenant #' . $log->tenant_id }}
                    </td>
                    <td class="p-4 font-mono text-emerald-400 font-bold">
                        {{ $log->device_id }}
                    </td>
                    <td class="p-4">
                        <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded border {{ $log->direction === 'push' ? 'bg-blue-500/10 text-blue-400 border-blue-500/30' : 'bg-purple-500/10 text-purple-400 border-purple-500/30' }}">
                            {{ $log->direction }}
                        </span>
                    </td>
                    <td class="p-4">
                        <span class="font-mono text-[11px] text-slate-400">
                            {{ is_array($log->tables_synced) ? implode(', ', $log->tables_synced) : ($log->tables_synced ?? 'All') }}
                        </span>
                    </td>
                    <td class="p-4 font-mono font-bold">
                        {{ $log->direction === 'push' ? "+{$log->records_pushed} pushed" : "+{$log->records_pulled} pulled" }}
                    </td>
                    <td class="p-4 font-mono text-amber-400 font-bold">
                        {{ $log->conflicts_resolved }}
                    </td>
                    <td class="p-4">
                        <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded border {{ $log->status === 'success' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30' }}">
                            {{ $log->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-6 text-center text-slate-500">No sync events recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\SyncLog;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * POST /api/sync/push
     * Receive bulk change payload from an Electron client.
     */
    public function push(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'changes'   => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid device_id and changes payload.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $tenantId = $request->input('_tenant_id') ?? ($user instanceof \App\Models\Tenant ? $user->id : ($user->tenant_id ?? null));
        $deviceId = $request->input('device_id');
        $changes  = $request->input('changes', []);

        $result = $this->syncService->processPush($tenantId, $deviceId, $changes);

        return response()->json($result, 200);
    }

    /**
     * GET /api/sync/pull
     * Return all changes since timestamp.
     */
    public function pull(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $request->input('_tenant_id') ?? ($user instanceof \App\Models\Tenant ? $user->id : ($user->tenant_id ?? null));
        $deviceId = $request->input('device_id', 'Terminal_Unknown');
        $since = $request->input('since'); // ISO date string or timestamp

        $result = $this->syncService->processPull($tenantId, $deviceId, $since);

        return response()->json($result, 200);
    }

    /**
     * GET /api/sync/logs
     * Return recent sync logs for the tenant.
     */
    public function logs(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $logs = SyncLog::where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'logs'    => $logs,
        ]);
    }
}

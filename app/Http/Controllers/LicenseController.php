<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * POST /api/license/activate
     * Device activation via License Key.
     */
    public function activate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'device_id'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid license key.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $deviceId = $request->input('device_id', 'Terminal_' . substr(md5($request->ip() . $request->header('User-Agent')), 0, 8));
        $result = $this->licenseService->activateKey($request->input('license_key'), $deviceId);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 200);
    }

    /**
     * POST /api/license/validate
     * License validation & expiration check.
     */
    public function validateKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid'   => false,
                'message' => 'License key is required.',
            ], 422);
        }

        $result = $this->licenseService->validateKey($request->input('license_key'));
        return response()->json($result, $result['valid'] ? 200 : 400);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * GET /api/settings
     */
    public function getSettings(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key')->all();

        return response()->json([
            'success'  => true,
            'settings' => $settings,
        ]);
    }

    /**
     * POST /api/settings
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $settingsData = $request->except(['_tenant_id', '_license_id', '_license_plan']);

        foreach ($settingsData as $key => $value) {
            if ($value !== null) {
                Setting::setValue($key, (string) $value);
            }
        }

        $allSettings = Setting::all()->pluck('value', 'key')->all();

        return response()->json([
            'success'  => true,
            'message'  => 'Settings saved successfully.',
            'settings' => $allSettings,
        ]);
    }
}

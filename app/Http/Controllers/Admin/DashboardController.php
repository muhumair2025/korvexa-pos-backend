<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\SyncLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function loginView()
    {
        if (Auth::guard('super_admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::guard('super_admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function index()
    {
        $stats = [
            'total_tenants'    => Tenant::count(),
            'active_tenants'   => Tenant::where('status', 'active')->count(),
            'active_licenses'  => License::where('status', 'active')->count(),
            'expired_licenses' => License::where('status', 'expired')->count(),
            'total_users'      => User::withoutTenantScope()->count(),
            'total_products'   => Product::withoutTenantScope()->count(),
            'total_orders'     => Order::withoutTenantScope()->count(),
            'total_sync_logs'  => SyncLog::count(),
        ];

        $recentTenants = Tenant::with(['licenses', 'users'])
            ->latest()
            ->take(10)
            ->get();

        $recentSyncs = SyncLog::with('tenant')
            ->latest('synced_at')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentTenants', 'recentSyncs'));
    }

    public function tenants(Request $request)
    {
        $query = Tenant::with(['licenses', 'users']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('business_name', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('owner_email', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tenants = $query->latest()->paginate(15);

        return view('admin.tenants', compact('tenants'));
    }

    public function toggleTenantStatus(int $id)
    {
        $tenant = Tenant::findOrFail($id);
        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->update(['status' => $newStatus]);

        return back()->with('success', "Tenant account {$tenant->business_name} status updated to {$newStatus}.");
    }

    public function licenses(Request $request)
    {
        $licenses = License::with('tenant')
            ->latest()
            ->paginate(15);

        return view('admin.licenses', compact('licenses'));
    }

    public function createLicense(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'owner_name'    => 'required|string|max:255',
            'owner_email'   => 'required|email|max:255',
            'owner_phone'   => 'nullable|string|max:50',
            'plan'          => 'required|string|in:starter,professional,enterprise',
            'duration_days' => 'required|integer|min:1',
            'max_counters'  => 'required|integer|min:0',
        ]);

        $result = $this->licenseService->createTenantWithLicense(
            [
                'business_name' => $data['business_name'],
                'owner_name'    => $data['owner_name'],
                'owner_email'   => $data['owner_email'],
                'owner_phone'   => $data['owner_phone'] ?? null,
            ],
            $data['plan'],
            (int) $data['max_counters'],
            (int) $data['duration_days']
        );

        return back()->with('success', "License Key Generated: {$result['license']->license_key} for {$result['tenant']->business_name}");
    }

    public function extendLicense(Request $request, int $id)
    {
        $license = License::findOrFail($id);
        $days = (int) $request->input('days', 30);
        
        $newExpiry = $license->expires_at && $license->expires_at->isFuture()
            ? $license->expires_at->addDays($days)
            : now()->addDays($days);

        $license->update([
            'expires_at' => $newExpiry,
            'status'     => 'active',
        ]);

        return back()->with('success', "License extended by {$days} days. New Expiry: {$newExpiry->format('M d, Y')}");
    }

    public function revokeLicense(int $id)
    {
        $license = License::findOrFail($id);
        $license->update(['status' => 'revoked']);

        return back()->with('success', "License key {$license->license_key} has been revoked.");
    }

    public function syncLogs(Request $request)
    {
        $logs = SyncLog::with('tenant')
            ->latest('synced_at')
            ->paginate(25);

        return view('admin.sync-logs', compact('logs'));
    }
}

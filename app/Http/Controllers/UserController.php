<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::orderBy('id', 'asc')->get()->map(function ($u) {
            return [
                'id'             => $u->id,
                'uuid'           => $u->uuid,
                'username'       => $u->username,
                'full_name'      => $u->full_name,
                'role'           => $u->role,
                'permissions'    => $u->getEffectivePermissions(),
                'shift_schedule' => $u->shift_schedule,
                'max_cash_limit' => (float) $u->max_cash_limit,
                'avatar'         => $u->avatar,
                'created_at'     => $u->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'users'   => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username'  => 'required|string|max:100',
            'full_name' => 'required|string|max:255',
            'password'  => 'required|string|min:4',
            'role'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $username = strtolower(trim($request->input('username')));
        if (User::where('username', $username)->exists()) {
            return response()->json(['success' => false, 'message' => 'Username already exists.'], 400);
        }

        $user = User::create([
            'uuid'           => $request->input('uuid') ?? (string) Str::uuid(),
            'username'       => $username,
            'full_name'      => trim($request->input('full_name')),
            'password'       => Hash::make($request->input('password')),
            'role'           => $request->input('role', 'Cashier'),
            'permissions'    => $request->input('permissions', []),
            'shift_schedule' => $request->input('shift_schedule', 'Flexible / Full Day'),
            'max_cash_limit' => (float) $request->input('max_cash_limit', 1000.0),
            'avatar'         => $request->input('avatar'),
            'synced_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'user'    => $user,
            'users'   => $this->index()->getData()->users,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = is_numeric($id) ? User::find($id) : User::where('uuid', $id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $data = [
            'full_name'      => trim($request->input('full_name', $user->full_name)),
            'role'           => $request->input('role', $user->role),
            'permissions'    => $request->input('permissions', $user->permissions),
            'shift_schedule' => $request->input('shift_schedule', $user->shift_schedule),
            'max_cash_limit' => (float) $request->input('max_cash_limit', $user->max_cash_limit),
            'synced_at'      => now(),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'user'    => $user,
            'users'   => $this->index()->getData()->users,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = is_numeric($id) ? User::find($id) : User::where('uuid', $id)->first();

        if ($user) {
            // Prevent deleting the primary admin if it's the only user
            if (User::count() <= 1) {
                return response()->json(['success' => false, 'message' => 'Cannot delete the primary administrator user.'], 400);
            }
            $user->delete();
        }

        return response()->json([
            'success' => true,
            'users'   => $this->index()->getData()->users,
        ]);
    }
}

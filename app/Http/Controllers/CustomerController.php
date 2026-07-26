<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        $customers = Customer::orderBy('id', 'desc')->get();

        return response()->json([
            'success'   => true,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:50',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string',
            'loyalty_points' => 'nullable|integer',
            'tier'           => 'nullable|string',
            'total_spent'    => 'nullable|numeric',
            'credit_balance' => 'nullable|numeric',
            'notes'          => 'nullable|string',
            'uuid'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $phone = trim($request->input('phone'));
        $exists = Customer::where('phone', $phone)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone number already exists.',
            ], 400);
        }

        $points = (int) $request->input('loyalty_points', 0);
        $tier = $request->input('tier') ?? ($points >= 800 ? 'Platinum' : ($points >= 400 ? 'Gold' : ($points >= 150 ? 'Silver' : 'Bronze')));

        $customer = Customer::create([
            'uuid'           => $request->input('uuid') ?? (string) Str::uuid(),
            'name'           => trim($request->input('name')),
            'phone'          => $phone,
            'email'          => $request->input('email'),
            'address'        => $request->input('address'),
            'city'           => $request->input('city'),
            'loyalty_points' => $points,
            'tier'           => $tier,
            'total_spent'    => (float) $request->input('total_spent', 0),
            'credit_balance' => (float) $request->input('credit_balance', 0),
            'notes'          => $request->input('notes'),
            'synced_at'      => now(),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Customer created successfully.',
            'customer'  => $customer,
            'customers' => Customer::orderBy('id', 'desc')->get(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $customer = is_numeric($id) ? Customer::find($id) : Customer::where('uuid', $id)->first();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }
        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $customer = is_numeric($id) ? Customer::find($id) : Customer::where('uuid', $id)->first();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        if ($request->has('phone') && trim($request->input('phone')) !== $customer->phone) {
            $phoneExists = Customer::where('phone', trim($request->input('phone')))->where('id', '!=', $customer->id)->exists();
            if ($phoneExists) {
                return response()->json(['success' => false, 'message' => 'Customer phone number already exists.'], 400);
            }
        }

        $customer->update(array_merge($request->only([
            'name', 'phone', 'email', 'address', 'city',
            'loyalty_points', 'tier', 'total_spent', 'credit_balance', 'notes'
        ]), ['synced_at' => now()]));

        return response()->json([
            'success'   => true,
            'message'   => 'Customer updated successfully.',
            'customer'  => $customer,
            'customers' => Customer::orderBy('id', 'desc')->get(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $customer = is_numeric($id) ? Customer::find($id) : Customer::where('uuid', $id)->first();
        if ($customer) {
            $customer->delete();
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Customer deleted.',
            'customers' => Customer::orderBy('id', 'desc')->get(),
        ]);
    }

    public function deleteMultiple(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (is_array($ids) && count($ids) > 0) {
            Customer::whereIn('id', $ids)->orWhereIn('uuid', $ids)->delete();
        }

        return response()->json([
            'success'   => true,
            'customers' => Customer::orderBy('id', 'desc')->get(),
        ]);
    }
}

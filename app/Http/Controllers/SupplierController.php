<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    public function index(): JsonResponse
    {
        $suppliers = Supplier::orderBy('id', 'desc')->get();

        return response()->json([
            'success'   => true,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone'          => 'required|string|max:50',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string',
            'category'       => 'nullable|string',
            'lead_time_days' => 'nullable|integer',
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
        $name = trim($request->input('name'));

        if (Supplier::where('phone', $phone)->exists()) {
            return response()->json(['success' => false, 'message' => 'Supplier phone number already exists.'], 400);
        }

        $supplier = Supplier::create([
            'uuid'           => $request->input('uuid') ?? (string) Str::uuid(),
            'name'           => $name,
            'contact_person' => trim($request->input('contact_person')),
            'phone'          => $phone,
            'email'          => $request->input('email'),
            'address'        => $request->input('address'),
            'category'       => $request->input('category'),
            'lead_time_days' => (int) $request->input('lead_time_days', 3),
            'notes'          => $request->input('notes'),
            'synced_at'      => now(),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Supplier created successfully.',
            'supplier'  => $supplier,
            'suppliers' => Supplier::orderBy('id', 'desc')->get(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $supplier = is_numeric($id) ? Supplier::find($id) : Supplier::where('uuid', $id)->first();
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Supplier not found.'], 404);
        }
        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $supplier = is_numeric($id) ? Supplier::find($id) : Supplier::where('uuid', $id)->first();
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Supplier not found.'], 404);
        }

        $supplier->update(array_merge($request->only([
            'name', 'contact_person', 'phone', 'email',
            'address', 'category', 'lead_time_days', 'notes'
        ]), ['synced_at' => now()]));

        return response()->json([
            'success'   => true,
            'message'   => 'Supplier updated successfully.',
            'supplier'  => $supplier,
            'suppliers' => Supplier::orderBy('id', 'desc')->get(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $supplier = is_numeric($id) ? Supplier::find($id) : Supplier::where('uuid', $id)->first();
        if ($supplier) {
            $supplier->delete();
        }

        return response()->json([
            'success'   => true,
            'suppliers' => Supplier::orderBy('id', 'desc')->get(),
        ]);
    }

    public function deleteMultiple(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (is_array($ids) && count($ids) > 0) {
            Supplier::whereIn('id', $ids)->orWhereIn('uuid', $ids)->delete();
        }

        return response()->json([
            'success'   => true,
            'suppliers' => Supplier::orderBy('id', 'desc')->get(),
        ]);
    }
}

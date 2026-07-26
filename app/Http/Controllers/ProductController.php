<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * List all products for the authenticated tenant.
     */
    public function index(): JsonResponse
    {
        $products = Product::orderBy('id', 'desc')->get();

        return response()->json([
            'success'  => true,
            'products' => $products,
        ]);
    }

    /**
     * POST /api/products
     * Create a new product.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'sku'         => 'nullable|string|max:100',
            'category'    => 'nullable|string|max:100',
            'price'       => 'required|numeric|min:0',
            'cost'        => 'nullable|numeric|min:0',
            'stock'       => 'nullable|integer',
            'image'       => 'nullable|string',
            'description' => 'nullable|string',
            'brand'       => 'nullable|string',
            'supplier_id' => 'nullable|integer',
            'supplier_name' => 'nullable|string',
            'uuid'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $sku = $request->input('sku') ? trim($request->input('sku')) : '1000' . rand(1000, 9999);

        // Check unique SKU per tenant
        $exists = Product::where('sku', $sku)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode / SKU already exists.',
            ], 400);
        }

        $product = Product::create([
            'uuid'          => $request->input('uuid') ?? (string) Str::uuid(),
            'sku'           => $sku,
            'name'          => trim($request->input('name')),
            'category'      => $request->input('category', 'General'),
            'price'         => (float) $request->input('price', 0),
            'cost'          => (float) $request->input('cost', 0),
            'stock'         => (int) $request->input('stock', 0),
            'image'         => $request->input('image'),
            'description'   => $request->input('description'),
            'brand'         => $request->input('brand'),
            'supplier_id'   => $request->input('supplier_id'),
            'supplier_name' => $request->input('supplier_name'),
            'synced_at'     => now(),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Product saved successfully.',
            'product'  => $product,
            'products' => Product::orderBy('id', 'desc')->get(),
        ], 201);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(string $id): JsonResponse
    {
        $product = is_numeric($id) ? Product::find($id) : Product::where('uuid', $id)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 440);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = is_numeric($id) ? Product::find($id) : Product::where('uuid', $id)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'sku'         => 'sometimes|required|string|max:100',
            'category'    => 'nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'cost'        => 'nullable|numeric|min:0',
            'stock'       => 'nullable|integer',
            'image'       => 'nullable|string',
            'description' => 'nullable|string',
            'brand'       => 'nullable|string',
            'supplier_id' => 'nullable|integer',
            'supplier_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->has('sku') && $request->input('sku') !== $product->sku) {
            $skuExists = Product::where('sku', $request->input('sku'))->where('id', '!=', $product->id)->exists();
            if ($skuExists) {
                return response()->json(['success' => false, 'message' => 'Barcode / SKU already exists.'], 400);
            }
        }

        $product->update(array_merge($request->only([
            'sku', 'name', 'category', 'price', 'cost', 'stock',
            'image', 'description', 'brand', 'supplier_id', 'supplier_name'
        ]), ['synced_at' => now()]));

        return response()->json([
            'success'  => true,
            'message'  => 'Product updated successfully.',
            'product'  => $product,
            'products' => Product::orderBy('id', 'desc')->get(),
        ]);
    }

    /**
     * DELETE /api/products/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $product = is_numeric($id) ? Product::find($id) : Product::where('uuid', $id)->first();

        if ($product) {
            $product->delete();
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Product deleted.',
            'products' => Product::orderBy('id', 'desc')->get(),
        ]);
    }

    /**
     * POST /api/products/delete-multiple
     */
    public function deleteMultiple(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (is_array($ids) && count($ids) > 0) {
            Product::whereIn('id', $ids)->orWhereIn('uuid', $ids)->delete();
        }

        return response()->json([
            'success'  => true,
            'products' => Product::orderBy('id', 'desc')->get(),
        ]);
    }
}

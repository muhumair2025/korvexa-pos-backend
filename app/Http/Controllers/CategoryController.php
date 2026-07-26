<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name', 'asc')->get();

        return response()->json([
            'success'    => true,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'uuid' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $name = trim($request->input('name'));
        $exists = Category::where('name', $name)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Category already exists.'], 400);
        }

        $category = Category::create([
            'uuid'      => $request->input('uuid') ?? (string) Str::uuid(),
            'name'      => $name,
            'synced_at' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Category created.',
            'category'   => $category,
            'categories' => Category::orderBy('name', 'asc')->get(),
        ], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = is_numeric($id) ? Category::find($id) : Category::where('uuid', $id)->first();
        if ($category) {
            $category->delete();
        }

        return response()->json([
            'success'    => true,
            'categories' => Category::orderBy('name', 'asc')->get(),
        ]);
    }
}

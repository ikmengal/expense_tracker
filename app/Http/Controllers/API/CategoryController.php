<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use App\Models\Category;
    use Illuminate\Http\Request;

    class CategoryController extends Controller
    {
        public function index(Request $request)
        {
            // User ki apni saari categories fetch karein
            $categories = $request->user()->categories;
            return response()->json($categories, 200);
        }

        public function store(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:income,expense',
                'icon' => 'nullable|string'
            ]);

            $category = $request->user()->categories()->create($request->all());

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);
        }

        public function show(Request $request, Category $category)
        {
            // Security check: Kisi aur ki category access na ho
            if ($category->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return response()->json($category, 200);
        }

        public function update(Request $request, Category $category)
        {
            if ($category->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:income,expense',
            ]);

            $category->update($request->all());

            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category
            ], 200);
        }

        public function destroy(Request $request, Category $category)
        {
            if ($category->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $category->delete();
            return response()->json(['message' => 'Category deleted successfully'], 200);
        }
    }

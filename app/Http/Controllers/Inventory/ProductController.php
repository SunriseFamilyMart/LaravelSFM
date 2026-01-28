<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Model\Product;
use Illuminate\Http\Request;
use App\Model\Category;



class ProductController extends Controller
{
    public function __construct(
        private Category $category,
        private Product $product,
    ) {
    }

    public function getSubcategories($parentId)
    {
        $subcategories = Category::where('parent_id', $parentId)->where('status', 1)->get();
        return response()->json($subcategories);
    }
    public function create()
    {
        $categories = $this->category->where(['position' => 0])->get();

        return view('inventory.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // ✅ 1. Validate form input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|in:percent,amount',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percent,amount',
            'unit' => 'nullable|string|max:20',
            'capacity' => 'nullable|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:categories,id',
            'total_stock' => 'required|integer|min:0',
            'tags' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // ✅ 2. Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = date('Y-m-d-') . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/product'), $filename);
            $imagePath = $filename;
        }

        // ✅ 3. Create product
        $product = new \App\Model\Product();
        $product->name = $validated['name'];
        $product->description = $validated['description'] ?? null;
        $product->price = $validated['price'];
        $product->tax = $validated['tax'] ?? 0;
        $product->tax_type = $validated['tax_type'] ?? 'percent';
        $product->discount = $validated['discount'] ?? 0;
        $product->discount_type = $validated['discount_type'] ?? 'percent';
        $product->unit = $validated['unit'] ?? 'pcs';
        $product->capacity = $validated['capacity'] ?? 0;
        $product->total_stock = $validated['total_stock'];
        $product->category_ids = json_encode([
            ['id' => $validated['category_id'], 'position' => 1]
        ]);
        if (!empty($validated['subcategory_id'])) {
            $product->category_ids = json_encode([
                ['id' => $validated['category_id'], 'position' => 1],
                ['id' => $validated['subcategory_id'], 'position' => 2]
            ]);
        }
        $product->status = 1;
        $product->image = $imagePath ? json_encode([$imagePath]) : json_encode([]);
        $product->save();

        // ✅ 4. Handle product tags
        if (!empty($validated['tags'])) {
            $tags = array_filter(array_map('trim', explode(',', $validated['tags'])));
            $tagIds = [];
            foreach ($tags as $tagName) {
                $tag = \App\Model\Tag::firstOrCreate(['tag' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $product->tags()->sync($tagIds); // Assuming relation exists in Product model
        }

        // ✅ 5. Redirect
        return redirect()->route('inventory.products.index')->with('success', 'Product added successfully!');
    }

}

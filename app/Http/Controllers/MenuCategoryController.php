<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuCategoryController extends Controller
{
    public function index()
    {
        $restaurantId = auth()->user()->restaurantData->id;
        
        $categories = MenuCategory::with('dishes')
            ->forRestaurant($restaurantId)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('MenuCategories/Index', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        MenuCategory::create([
            'restaurant_id' => auth()->user()->restaurantData->id,
            'category_name' => $validated['category_name'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('menu-categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function update(Request $request, MenuCategory $menuCategory)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $menuCategory->update($validated);

        return redirect()->route('menu-categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(MenuCategory $menuCategory)
    {
        // Check if category has dishes
        if ($menuCategory->dishes()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete category with existing dishes!');
        }

        $menuCategory->delete();

        return redirect()->route('menu-categories.index')
            ->with('success', 'Category deleted successfully!');
    }
}

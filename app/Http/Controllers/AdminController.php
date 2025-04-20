<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Category;
use App\Models\Preference;
use App\Models\Vote;

class AdminController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return Inertia::render('Admin/Index', [
            'categories' => $categories
        ]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin');
    }

    public function deleteCategory(Category $category)
    {
        if ($category->preferences()->count() > 0) {
            return redirect()->route('admin')->with('error', 'No se puede eliminar una categoría con preferencias asociadas.');
        }
        $category->delete();
        return redirect()->route('admin')->with('success', 'Categoría eliminada correctamente.');
    }

    public function showCategory(Category $category)
    {
        $category->load(['preferences' => function ($query) {
            $query->withCount('votes');
        }]);

        $preferences = $category->preferences->map(function ($preference) {
            $preference->preference1_votes_count = $preference->preference1_votes;
            $preference->preference2_votes_count = $preference->preference2_votes;
            return $preference;
        });

        return Inertia::render('Admin/CategoryShow', [
            'category' => $category,
            'preferences' => $preferences
        ]);
    }

    public function storePreference(Request $request, Category $category)
    {
        $request->validate([
            'preference1' => 'required|string|max:255',
            'preference2' => 'required|string|max:255|different:preference1',
        ]);

        $category->preferences()->create($request->only('preference1', 'preference2'));

        return redirect()->route('admin.categories.show', $category->id)->with('success', 'Preferencia creada correctamente.');
    }

    public function updatePreference(Request $request, Preference $preference)
    {
        $request->validate([
            'preference1' => 'required|string|max:255',
            'preference2' => 'required|string|max:255|different:preference1',
        ]);

        $preference->update($request->only('preference1', 'preference2'));

        return redirect()->route('admin.categories.show', $preference->category_id)->with('success', 'Preferencia actualizada correctamente.');
    }

    public function deletePreference(Preference $preference)
    {
        $categoryId = $preference->category_id;

        $preference->votes()->delete();

        $preference->delete();

        return redirect()->route('admin.categories.show', $categoryId)->with('success', 'Preferencia eliminada correctamente.');
    }
}

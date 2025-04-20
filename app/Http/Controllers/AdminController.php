<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Category;
use App\Models\Preference;
use App\Models\Vote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();
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
        // Cargar las preferencias ordenadas. Los accessors se encargarán de los conteos.
        $preferences = $category->preferences()
                                // ->withCount('preference1Votes', 'preference2Votes') // Eliminado: Usaremos los accessors existentes
                                ->orderBy('created_at', 'desc')
                                ->get();

        return Inertia::render('Admin/CategoryShow', [
            'category' => $category,
            'preferences' => $preferences, // Los accessors 'preference1_votes' y 'preference2_votes' se añadirán automáticamente
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

    public function bulkStorePreferences(Request $request, Category $category)
    {
        $validated = $request->validate([
            'json_data' => 'required|string',
        ]);

        $preferencesData = json_decode($validated['json_data'], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($preferencesData)) {
            throw ValidationException::withMessages([
                'json_data' => 'El JSON proporcionado no es válido o no es un array.',
            ]);
        }

        if (empty($preferencesData)) {
            return redirect()->route('admin.categories.show', $category)
                        ->with('warning', 'El JSON estaba vacío, no se importaron preferencias.');
        }

        $rules = [
            '*.preference1' => 'required|string|max:255',
            '*.preference2' => 'required|string|max:255|different:*.preference1',
        ];

        $messages = [
            '*.preference1.required' => 'La opción 1 es requerida para todas las preferencias.',
            '*.preference1.string' => 'La opción 1 debe ser texto.',
            '*.preference1.max' => 'La opción 1 no puede exceder los 255 caracteres.',
            '*.preference2.required' => 'La opción 2 es requerida para todas las preferencias.',
            '*.preference2.string' => 'La opción 2 debe ser texto.',
            '*.preference2.max' => 'La opción 2 no puede exceder los 255 caracteres.',
            '*.preference2.different' => 'La opción 1 y la opción 2 no pueden ser iguales en la misma preferencia.'
        ];

        $validator = Validator::make($preferencesData, $rules, $messages);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'json_data' => 'El formato de algunas preferencias en el JSON no es válido: ' . $validator->errors()->first(),
            ]);
        }

        try {
            DB::transaction(function () use ($preferencesData, $category) {
                foreach ($preferencesData as $prefData) {
                    $category->preferences()->create([
                        'preference1' => $prefData['preference1'],
                        'preference2' => $prefData['preference2'],
                    ]);
                }
            });
        } catch (\Exception $e) {
            \Log::error('Error en bulkStorePreferences: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'json_data' => 'Ocurrió un error interno al guardar las preferencias. Por favor, revisa el formato e inténtalo de nuevo.',
            ]);
        }

        return redirect()->route('admin.categories.show', $category)
                     ->with('success', count($preferencesData) . ' preferencias importadas masivamente con éxito.');
    }
}

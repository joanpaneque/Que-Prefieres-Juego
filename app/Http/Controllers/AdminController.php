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
use Illuminate\Support\Facades\Redirect;

use App\Services\OpenAIService;

class AdminController extends Controller
{
    public function index()
    {
        // Order categories by position, load preference count, and total votes count
        $categories = Category::query()
            ->withCount('preferences') // Cuenta las preferencias por categoría
            ->addSelect(['total_votes' => Vote::selectRaw('count(*)') // Subconsulta para contar votos
                ->join('preferences', 'votes.preference_id', '=', 'preferences.id')
                ->whereColumn('preferences.category_id', 'categories.id') // Filtra por categoría
            ])
            ->orderBy('position', 'asc')
            ->get();

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
                                ->orderBy('updated_at', 'desc')
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
        $validatedData = $request->validate([
            'preference1' => 'required|string|max:255',
            'preference2' => 'required|string|max:255|different:preference1',
            'human_validated' => 'required|boolean',
        ]);

        $preference->update($validatedData);

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



        // The original try-catch block for saving preferences is kept below,
        // but you might want to integrate the duplication check result
        // (e.g., only save if $potentialDuplicates is empty, or ask user for confirmation)
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

    /**
     * Toggle the human_validated status of a preference.
     *
     * @param  \App\Models\Preference  $preference
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleValidation(Preference $preference)
    {
        // Opcional: añadir autorización para asegurar que el usuario puede modificar esta preferencia
        // Gate::authorize('update', $preference);

        $preference->human_validated = !$preference->human_validated;
        $preference->save();

        // Inertia recibirá la actualización de props automáticamente al redirigir.
        return Redirect::back()->with('success', 'Estado de validación actualizado.');
    }

    public function updateCategoryOrder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer|exists:categories,id',
            'categories.*.position' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['categories'] as $categoryData) {
                Category::where('id', $categoryData['id'])->update(['position' => $categoryData['position']]);
            }
        });

        // Redirect back or return a success response
        // Using back() with Inertia might require specific handling if you want flash messages
        return Redirect::back()->with('success', 'Category order updated successfully.');
        // Alternatively, for API-like responses if not using Inertia redirects for this:
        // return response()->json(['message' => 'Category order updated successfully.']);
    }
}

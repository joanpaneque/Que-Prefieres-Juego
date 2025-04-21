<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;

use App\Models\Preference;
use App\Models\Vote;
use App\Models\Category;

class GameController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('position', 'asc')->get();
        return Inertia::render('Game/Index', [
            'categories' => $categories
        ]);
    }

    public function jsonGetNewPreference(Request $request)
    {
        $preferencesToSkip = $request->input('preferencesToSkip', []);
        $categoryId = $request->input('categoryId');

        // Start query for validated preferences
        $query = Preference::where('human_validated', true);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Attempt to find a validated preference not in the skip list
        $preference = $query->whereNotIn('id', $preferencesToSkip)
                             ->inRandomOrder()
                             ->first();

        if (!$preference) {
            // Check if *any* validated preferences exist for the category (ignoring skip list)
            $remainingValidatedQuery = Preference::where('human_validated', true);
            if ($categoryId) {
                 $remainingValidatedQuery->where('category_id', $categoryId);
            }
            // Count only validated ones not in skip list
            $remainingValidatedCount = (clone $remainingValidatedQuery) // Clone to avoid modifying the original query object
                                           ->whereNotIn('id', $preferencesToSkip)
                                           ->count();

            if ($remainingValidatedCount === 0) {
                 // User has seen all available validated preferences in this category/overall
                 return response()->json(['preference' => null, 'message' => 'No more validated preferences found']);
            } else {
                // Should ideally not happen if the query logic is correct, but as a fallback
                // Might indicate an issue fetching a specific preference that exists
                 return response()->json(['preference' => null, 'message' => 'Could not fetch a validated preference']);
            }
        }

        return response()->json(['preference' => $preference]);
    }

    public function jsonVote(Request $request)
    {
        $vote = Vote::create($request->all());

        return response()->json($vote);
    }
}

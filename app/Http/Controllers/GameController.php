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
        $categories = Category::all();
        return Inertia::render('Game/Index', [
            'categories' => $categories
        ]);
    }

    public function jsonGetNewPreference(Request $request)
    {
        $preferencesToSkip = $request->input('preferencesToSkip', []);
        $categoryId = $request->input('categoryId');

        $query = Preference::query();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $preference = $query->whereNotIn('id', $preferencesToSkip)
                             ->inRandomOrder()
                             ->first();

        if (!$preference) {
            $remainingQuery = Preference::query();
            if ($categoryId) {
                 $remainingQuery->where('category_id', $categoryId);
            }
            $remainingCount = $remainingQuery->whereNotIn('id', $preferencesToSkip)->count();

            if ($remainingCount === 0) {
                 return response()->json(['preference' => null, 'message' => 'No more preferences found']);
            } else {
                return response()->json(['preference' => null, 'message' => 'Could not fetch preference']);
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

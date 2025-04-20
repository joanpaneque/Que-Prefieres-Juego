<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;

use App\Models\Preference;
use App\Models\Vote;

class GameController extends Controller
{
    public function index()
    {
        return Inertia::render('Game/Index');
    }

    public function jsonGetNewPreference(Request $request)
    {
        $preferencesToSkip = $request->input('preferencesToSkip', []);

        $preference = Preference::whereNotIn('id', $preferencesToSkip)->inRandomOrder()->first();

        return response()->json($preference);
    }

    public function jsonVote(Request $request)
    {
        $vote = Vote::create($request->all());

        return response()->json($vote);
    }
}

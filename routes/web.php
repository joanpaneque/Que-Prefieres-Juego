<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\GameController;

Route::get('/', [GameController::class, 'index']);
Route::get('/api/get-new-preference', [GameController::class, 'jsonGetNewPreference']);
Route::post('/api/vote', [GameController::class, 'jsonVote']);
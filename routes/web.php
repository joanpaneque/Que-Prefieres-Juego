<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\GameController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\TestController;

Route::get('/', [GameController::class, 'index']);
Route::get('/api/get-new-preference', [GameController::class, 'jsonGetNewPreference']);
Route::post('/api/vote', [GameController::class, 'jsonVote']);

Route::get('/admin', [AdminController::class, 'index'])->middleware('auth')->name('admin');

// Rutas para la gestión de categorías
Route::post('/admin/categories', [AdminController::class, 'storeCategory'])->middleware('auth')->name('admin.categories.store');
Route::put('/admin/categories/{category}', [AdminController::class, 'updateCategory'])->middleware('auth')->name('admin.categories.update');
Route::delete('/admin/categories/{category}', [AdminController::class, 'deleteCategory'])->middleware('auth')->name('admin.categories.delete');
Route::get('/admin/categories/{category}', [AdminController::class, 'showCategory'])->middleware('auth')->name('admin.categories.show');

// Route for updating category order
Route::post('/admin/categories/update-order', [AdminController::class, 'updateCategoryOrder'])->middleware('auth')->name('admin.categories.updateOrder');

// Rutas para la gestión de preferencias dentro de una categoría
Route::post('/admin/categories/{category}/preferences', [AdminController::class, 'storePreference'])->middleware('auth')->name('admin.preferences.store');
Route::put('/admin/preferences/{preference}', [AdminController::class, 'updatePreference'])->middleware('auth')->name('admin.preferences.update');
Route::delete('/admin/preferences/{preference}', [AdminController::class, 'deletePreference'])->middleware('auth')->name('admin.preferences.delete');

// ++ Nueva ruta para la validación humana de preferencias ++
Route::patch('/admin/preferences/{preference}/toggle-validation', [AdminController::class, 'toggleValidation'])
    ->middleware('auth')
    ->name('admin.preferences.toggleValidation');

// ++ Nueva ruta para importación masiva de preferencias ++
Route::post('/admin/categories/{category}/preferences/bulk', [AdminController::class, 'bulkStorePreferences'])
    ->middleware('auth') // Asegúrate de que 'auth' sea suficiente, o añade 'admin' si tienes ese middleware
    ->name('admin.preferences.bulkStore');

// Rutas de autenticación (Login/Logout)
Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->middleware('guest')
                ->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'store'])
                ->middleware('guest');

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->middleware('auth')
                ->name('logout');

Route::get('/test', [TestController::class, 'index']);
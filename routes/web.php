<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::get('/comment-jouer', [PageController::class, 'howToPlay'])->name('how-to-play');
Route::get('/explorer', [PageController::class, 'explore'])->name('explore');
Route::get('/classement', [PageController::class, 'leaderboard'])->name('leaderboard');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/a-propos', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/lieux/{id}', [PageController::class, 'showPlace'])->name('places.show');

Route::get('/dashboard', function (Request $request) {
    if ($request->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\GameController;

// ...

// Game Routes
Route::middleware(['auth', 'verified'])->prefix('game')->name('game.')->group(function () {
    Route::post('/sessions', [GameController::class, 'createSession'])->name('create');
    Route::get('/sessions/{token}', [GameController::class, 'showSession'])->name('session');
    Route::post('/sessions/{session}/riddles/{riddle}', [GameController::class, 'submitAnswer'])->name('submit');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/places', [AdminController::class, 'places'])->name('places');
    Route::post('/places', [AdminController::class, 'storePlace'])->name('places.store');
    Route::post('/places/{place}/toggle', [AdminController::class, 'togglePlace'])->name('places.toggle');
    Route::get('/places/{place}/enigmas', [AdminController::class, 'riddles'])->name('enigmas');
    Route::post('/places/{place}/enigmas', [AdminController::class, 'storeRiddle'])->name('enigmas.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

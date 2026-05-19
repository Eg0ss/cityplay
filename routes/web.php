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
    // Rediriger les joueurs vers le nouveau tableau de bord Gaming
    return redirect()->route('game.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\GameEngineController;

// Game Engine Routes (Pure Gaming Mode)
Route::middleware(['auth', 'verified'])->prefix('game')->name('game.')->group(function () {
    Route::get('/dashboard', [GameEngineController::class, 'dashboard'])->name('dashboard');
    Route::get('/setup', [GameEngineController::class, 'setup'])->name('setup');
    Route::get('/progression', [GameEngineController::class, 'progression'])->name('progression');
    Route::post('/sessions', [GameEngineController::class, 'createSession'])->name('create');
    Route::get('/lobby/{token}', [GameEngineController::class, 'lobby'])->name('lobby');
    Route::post('/lobby/{token}/start', [GameEngineController::class, 'startGame'])->name('start');
    Route::get('/play/{token}', [GameEngineController::class, 'play'])->name('play');
    Route::post('/play/record', [GameEngineController::class, 'recordResult'])->name('record');
    Route::get('/riddle/{riddleId}/hints', [GameEngineController::class, 'getHints'])->name('hints');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Cities Management
    Route::get('/cities', [AdminController::class, 'cities'])->name('cities');
    Route::post('/cities', [AdminController::class, 'storeCity'])->name('cities.store');
    Route::post('/cities/{city}/update', [AdminController::class, 'updateCity'])->name('cities.update');
    Route::delete('/cities/{city}', [AdminController::class, 'deleteCity'])->name('cities.delete');
    
    // Places Management
    Route::get('/places', [AdminController::class, 'allPlaces'])->name('places.all');
    Route::post('/places', [AdminController::class, 'storeGlobalPlace'])->name('places.store_global');
    Route::post('/places/{place}/update', [AdminController::class, 'updatePlace'])->name('places.update');
    Route::delete('/places/{place}', [AdminController::class, 'deletePlace'])->name('places.delete');
    Route::get('/cities/{city}/places', [AdminController::class, 'places'])->name('cities.places');
    Route::post('/cities/{city}/places', [AdminController::class, 'storePlace'])->name('places.store');
    
    // Enigmas Management
    Route::get('/enigmas', [AdminController::class, 'allEnigmas'])->name('enigmas.all');
    Route::post('/enigmas/{enigma}/update', [AdminController::class, 'updateRiddle'])->name('enigmas.update');
    Route::delete('/enigmas/{enigma}', [AdminController::class, 'deleteEnigma'])->name('enigmas.delete');
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

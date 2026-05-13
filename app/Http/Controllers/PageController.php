<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function howToPlay() { return Inertia::render('HowToPlay'); }
    public function explore() { return Inertia::render('Explore'); }
    public function leaderboard() { return Inertia::render('Leaderboard'); }
    public function blog() { return Inertia::render('Blog'); }
    public function about() { return Inertia::render('About'); }
    public function contact() { return Inertia::render('Contact'); }
    public function showPlace($id) { return Inertia::render('ShowPlace', ['id' => $id]); }
}

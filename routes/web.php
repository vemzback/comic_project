<?php

use App\Http\Controllers\Admin\ComicController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComicDetailController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReaderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/comics', [HomeController::class, 'comics'])->name('comics');
Route::get('/comics/{comic}', [ComicDetailController::class, 'show'])->name('comic.detail');
Route::get('/comics/{comic}/chapters/{chapter}', [ReaderController::class, 'show'])->name('chapter.reader');
Route::get('/genres', [HomeController::class, 'genres'])->name('genres');
Route::get('/genres/{genre:slug}', [HomeController::class, 'genre'])->name('genres.show');
Route::get('/search', [HomeController::class, 'search'])->name('search');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('comics', ComicController::class);
});

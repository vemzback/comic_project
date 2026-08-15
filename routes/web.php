<?php

use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\ComicController;
use App\Http\Controllers\Admin\PageController;
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

    Route::get('/comics/{comic}/chapters', [ChapterController::class, 'index'])->name('comics.chapters.index');
    Route::get('/comics/{comic}/chapters/create', [ChapterController::class, 'create'])->name('comics.chapters.create');
    Route::post('/comics/{comic}/chapters', [ChapterController::class, 'store'])->name('comics.chapters.store');
    Route::get('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'show'])->name('comics.chapters.show');
    Route::get('/comics/{comic}/chapters/{chapter}/edit', [ChapterController::class, 'edit'])->name('comics.chapters.edit');
    Route::put('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'update'])->name('comics.chapters.update');
    Route::delete('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('comics.chapters.destroy');

    Route::get('/comics/{comic}/chapters/{chapter}/pages', [PageController::class, 'index'])->name('comics.chapters.pages.index');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/create', [PageController::class, 'create'])->name('comics.chapters.pages.create');
    Route::post('/comics/{comic}/chapters/{chapter}/pages', [PageController::class, 'store'])->name('comics.chapters.pages.store');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/{page}', [PageController::class, 'show'])->name('comics.chapters.pages.show');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/{page}/edit', [PageController::class, 'edit'])->name('comics.chapters.pages.edit');
    Route::put('/comics/{comic}/chapters/{chapter}/pages/{page}', [PageController::class, 'update'])->name('comics.chapters.pages.update');
    Route::delete('/comics/{comic}/chapters/{chapter}/pages/{page}', [PageController::class, 'destroy'])->name('comics.chapters.pages.destroy');
});

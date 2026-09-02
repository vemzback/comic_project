<?php

use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\ComicController;
use App\Http\Controllers\Admin\ComicImportController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\PageBulkImportController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ComicDetailController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReaderController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/comics', [HomeController::class, 'comics'])->name('comics');
Route::get('/comics/{comic}', [ComicDetailController::class, 'show'])->name('comic.detail');
Route::get('/comics/{comic}/comments', [CommentController::class, 'index'])->name('comments.feed');
Route::get('/comics/{comic}/chapters/{chapter}', [ReaderController::class, 'show'])->name('chapter.reader');
Route::get('/genres', [HomeController::class, 'genres'])->name('genres');
Route::get('/genres/{genre:slug}', [HomeController::class, 'genre'])->name('genres.show');
Route::get('/search', [HomeController::class, 'search'])->name('search');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.send');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');

    Route::get('/profile/password', [ProfileController::class, 'showPassword'])->name('password.edit');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::middleware('verified')->group(function () {
        Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
        Route::post('/bookmarks/{comic}', [BookmarkController::class, 'store'])->name('bookmarks.store');
        Route::delete('/bookmarks/{comic}', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');

        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::post('/comics/{comic}/chapters/{chapter}/progress', [ReaderController::class, 'progress'])
            ->middleware('throttle:120,1')
            ->name('reader.progress');

        Route::post('/comics/{comic}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('/comics/{comic}/ratings', [RatingController::class, 'store'])->name('ratings.store');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [UserController::class, 'updateRole'])
        ->middleware(ValidateCsrfToken::class)
        ->name('users.role.update');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}/approval', [AdminCommentController::class, 'updateApproval'])->name('comments.approval.update');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/comics/import', [ComicImportController::class, 'index'])->name('comics.import.index');
    Route::post('/comics/import', [ComicImportController::class, 'store'])->name('comics.import.store');
    Route::resource('comics', ComicController::class);

    Route::resource('genres', GenreController::class)->except('show');

    Route::get('/comics/{comic}/chapters', [ChapterController::class, 'index'])->name('comics.chapters.index');
    Route::get('/comics/{comic}/chapters/create', [ChapterController::class, 'create'])->name('comics.chapters.create');
    Route::post('/comics/{comic}/chapters', [ChapterController::class, 'store'])->name('comics.chapters.store');
    Route::get('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'show'])->name('comics.chapters.show');
    Route::get('/comics/{comic}/chapters/{chapter}/edit', [ChapterController::class, 'edit'])->name('comics.chapters.edit');
    Route::put('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'update'])->name('comics.chapters.update');
    Route::delete('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('comics.chapters.destroy');

    Route::get('/comics/{comic}/chapters/{chapter}/pages', [PageController::class, 'index'])->name('comics.chapters.pages.index');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/bulk-import', [PageBulkImportController::class, 'create'])->name('comics.chapters.pages.bulk.create');
    Route::post('/comics/{comic}/chapters/{chapter}/pages/bulk-import/preview', [PageBulkImportController::class, 'preview'])->name('comics.chapters.pages.bulk.preview');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/bulk-import/{token}/images/{position}', [PageBulkImportController::class, 'image'])
        ->whereNumber('position')
        ->name('comics.chapters.pages.bulk.image');
    Route::post('/comics/{comic}/chapters/{chapter}/pages/bulk-import/{token}', [PageBulkImportController::class, 'store'])->name('comics.chapters.pages.bulk.store');
    Route::delete('/comics/{comic}/chapters/{chapter}/pages/bulk-import/{token}', [PageBulkImportController::class, 'destroy'])->name('comics.chapters.pages.bulk.destroy');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/create', [PageController::class, 'create'])->name('comics.chapters.pages.create');
    Route::post('/comics/{comic}/chapters/{chapter}/pages', [PageController::class, 'store'])->name('comics.chapters.pages.store');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/{page}', [PageController::class, 'show'])->name('comics.chapters.pages.show');
    Route::get('/comics/{comic}/chapters/{chapter}/pages/{page}/edit', [PageController::class, 'edit'])->name('comics.chapters.pages.edit');
    Route::put('/comics/{comic}/chapters/{chapter}/pages/{page}', [PageController::class, 'update'])->name('comics.chapters.pages.update');
    Route::delete('/comics/{comic}/chapters/{chapter}/pages/{page}', [PageController::class, 'destroy'])->name('comics.chapters.pages.destroy');
});

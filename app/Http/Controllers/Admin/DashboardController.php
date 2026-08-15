<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $metrics = [
            ['label' => 'Total Users', 'value' => User::count(), 'icon' => '👥'],
            ['label' => 'Total Comics', 'value' => Comic::count(), 'icon' => '📚'],
            ['label' => 'Total Chapters', 'value' => Chapter::count(), 'icon' => '📖'],
            ['label' => 'Total Pages', 'value' => Page::count(), 'icon' => '🖼️'],
            ['label' => 'Bookmarks', 'value' => Bookmark::count(), 'icon' => '🔖'],
            ['label' => 'Reading History', 'value' => ReadingHistory::count(), 'icon' => '🕘'],
            ['label' => 'Comments', 'value' => Comment::count(), 'icon' => '💬'],
            ['label' => 'Ratings', 'value' => Rating::count(), 'icon' => '⭐'],
        ];

        $recentUsers = User::query()->latest()->limit(5)->get();
        $recentComics = Comic::query()->latest()->limit(5)->get();
        $recentChapters = Chapter::query()->with('comic')->latest()->limit(5)->get();
        $recentComments = Comment::query()->with(['user', 'comic'])->latest()->limit(5)->get();
        $recentRatings = Rating::query()->with(['user', 'comic'])->latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'metrics',
            'recentUsers',
            'recentComics',
            'recentChapters',
            'recentComments',
            'recentRatings'
        ));
    }
}

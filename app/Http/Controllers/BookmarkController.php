<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    public function index(): View
    {
        $bookmarks = Auth::user()
            ->bookmarks()
            ->with('comic')
            ->latest()
            ->get();

        return view('bookmarks.index', compact('bookmarks'));
    }

    public function store(Comic $comic): RedirectResponse
    {
        $user = Auth::user();

        if ($user->bookmarks()->where('comic_id', $comic->id)->exists()) {
            return redirect()->route('comic.detail', $comic)->with('status', 'This comic is already bookmarked.');
        }

        $user->bookmarks()->create([
            'comic_id' => $comic->id,
        ]);

        return redirect()->route('comic.detail', $comic)->with('success', 'Comic bookmarked.');
    }

    public function destroy(Comic $comic): RedirectResponse
    {
        $bookmark = Auth::user()
            ->bookmarks()
            ->where('comic_id', $comic->id)
            ->first();

        if (! $bookmark) {
            abort(404);
        }

        $bookmark->delete();

        return redirect()->route('bookmarks.index')->with('success', 'Bookmark removed.');
    }
}

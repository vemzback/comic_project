<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\View\View;

class ComicDetailController extends Controller
{
    public function show(Comic $comic): View
    {
        $comic->load([
            'chapters' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('sort_order')
                    ->orderBy('chapter_number');
            },
            'genres',
            'comments' => function ($query) {
                $query->where('is_approved', true)
                    ->with('user')
                    ->latest();
            },
        ]);

        $publishedChaptersCount = $comic->chapters->count();

        return view('public.comic-detail', compact('comic', 'publishedChaptersCount'));
    }
}

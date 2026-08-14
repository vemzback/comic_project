<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\View\View;

class ComicDetailController extends Controller
{
    public function show(Comic $comic): View
    {
        // Load chapters with pages
        $comic->load([
            'chapters' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('sort_order')
                    ->orderBy('chapter_number');
            },
            'genres',
        ]);

        // Get published chapters count
        $publishedChaptersCount = $comic->chapters->count();

        return view('public.comic-detail', compact('comic', 'publishedChaptersCount'));
    }
}

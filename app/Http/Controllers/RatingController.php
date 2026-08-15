<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, Comic $comic): RedirectResponse
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'between:1,5'],
        ]);

        $rating = $request->user()->ratings()->where('comic_id', $comic->id)->first();

        if ($rating) {
            $rating->update([
                'score' => $validated['score'],
            ]);
        } else {
            $request->user()->ratings()->create([
                'comic_id' => $comic->id,
                'score' => $validated['score'],
            ]);
        }

        return redirect()->route('comic.detail', $comic)->with('success', 'Your rating has been saved.');
    }
}

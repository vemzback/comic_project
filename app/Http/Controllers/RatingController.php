<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, Comic $comic): RedirectResponse|JsonResponse
    {
        if (! $comic->published_at || $comic->published_at->isFuture()) {
            abort(404);
        }

        $validated = $request->validate([
            'score' => ['required', 'integer', 'between:1,5'],
        ]);

        $request->user()->ratings()->updateOrCreate(
            [
                'comic_id' => $comic->id,
            ],
            [
                'score' => $validated['score'],
            ],
        );

        $ratingCount = $comic->ratings()->count();
        $averageRating = round((float) $comic->ratings()->average('score'), 1);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your rating has been saved.',
                'average_rating' => $averageRating,
                'rating_count' => $ratingCount,
                'user_score' => (int) $validated['score'],
            ]);
        }

        return redirect()->route('comic.detail', $comic)->with('success', 'Your rating has been saved.');
    }
}

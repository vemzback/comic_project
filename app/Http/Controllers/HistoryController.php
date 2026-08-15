<?php

namespace App\Http\Controllers;

use App\Models\ReadingHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(): View
    {
        $histories = ReadingHistory::where('user_id', Auth::id())
            ->with(['comic', 'chapter'])
            ->latest('last_read_at')
            ->get();

        return view('history.index', compact('histories'));
    }
}

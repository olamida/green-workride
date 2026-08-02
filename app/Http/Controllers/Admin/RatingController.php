<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RideRating;
use App\Models\User;

/**
 * Control Tower — trust layer. Recent mutual ratings plus a driver scoreboard
 * (average rating per driver, guide §8 dashboard driver scores).
 */
class RatingController extends Controller
{
    public function index()
    {
        $ratings = RideRating::with(['rater', 'ratee', 'booking.trip'])
            ->latest()
            ->limit(50)
            ->get();

        $scoreboard = User::query()
            ->withCount('ratingsReceived as rating_count')
            ->withAvg('ratingsReceived as rating_avg', 'rating')
            ->whereHas('ratingsReceived')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->limit(20)
            ->get();

        return view('admin.ratings.index', compact('ratings', 'scoreboard'));
    }
}

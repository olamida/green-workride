<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
{
    public function __construct(private RatingService $ratings) {}

    public function store(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->ratings->rate($request->user(), $booking, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Thanks! Your rating keeps WorkRide safe.');
    }
}

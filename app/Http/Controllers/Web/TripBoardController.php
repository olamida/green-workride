<?php

namespace App\Http\Controllers\Web;

use App\Enums\Corridor;
use App\Events\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\DriverScore;
use App\Models\Trip;
use App\Services\DemandService;
use App\Services\RatingService;
use App\Services\TripMatchingService;
use App\Services\TripService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TripBoardController extends Controller
{
    public function __construct(
        private TripService $trips,
        private RatingService $ratings,
    ) {}

    public function index(Request $request, TripMatchingService $matcher, DemandService $demand)
    {
        $corridor = $request->has('corridor') && $request->input('corridor')
            ? Corridor::from($request->input('corridor'))
            : null;

        // Women-only is a preference, not a hard sort: riders who opt in get
        // the filter defaulted on, everyone else sees it as an opt-in chip.
        $womenOnly = $request->has('women_only')
            ? $request->boolean('women_only')
            : (bool) (auth()->user()->prefers_women_only ?? false);

        // Departure window: the board defaults to the full planning horizon
        // (48h) so day-ahead trips are visible and bookable; "now" narrows it
        // to the classic 30-minute "leaving soon" view.
        $presets = (array) config('workride.board_window_presets', []);
        $window = $request->input('window', 'any');
        $withinMinutes = $presets[$window] ?? (int) config('workride.board_window_minutes', 2880);

        $trips = $matcher->upcoming($corridor, $withinMinutes, $womenOnly);

        // Demand-aware board (section 9B): pending check-ins become the empty
        // state's "N people want this journey" signal + the guide's live strip.
        $demandSnapshot = $demand->demandSnapshot();
        $nextTrip = $trips->first();

        // Corridors that are moving right now — pulses the corridor chips.
        $corridorLive = $matcher->liveCorridors();

        // Per-corridor availability within the selected window — the chip hero.
        $corridorStats = $matcher->corridorStats($withinMinutes);

        return view('trips.board', compact('trips', 'corridor', 'womenOnly', 'window', 'presets', 'demandSnapshot', 'nextTrip', 'corridorLive', 'corridorStats'));
    }

    public function create()
    {
        $user = auth()->user();

        abort_unless($user->canDriveVolunteer(), 403, 'Workplace verification (Level 1) is required to publish rides.');

        $vehicles = $user->vehicles()->get();
        $corridors = Corridor::cases();

        // Fleet gate preview: the driver's single assigned asset + today's
        // inspection status so they know whether publishing is blocked.
        $asset = null;
        $todayInspection = null;

        if (config('workride.fleet.enabled')) {
            $assigned = $user->assets()->get();
            $asset = $assigned->count() === 1 ? $assigned->first() : null;
            $todayInspection = $asset
                ? $asset->inspections()->whereDate('date', today())->latest('id')->first()
                : null;
        }

        return view('trips.create', compact('vehicles', 'corridors', 'asset', 'todayInspection'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isFreeVolunteer = $request->boolean('is_free_volunteer');

        if ($isFreeVolunteer) {
            abort_unless($user->canDriveVolunteer(), 403, 'Workplace verification (Level 1) is required to publish free volunteer rides.');
        } else {
            abort_unless($user->canDrivePaid(), 403, 'Driver verification (Level 3) is required to publish paid rides.');
        }

        $data = $request->validate($this->publishRules());

        try {
            $trip = $this->trips->publish($user, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('trips.show', $trip)
            ->with('status', 'Trip published. Passengers near '.$trip->route_name.' have been notified.');
    }

    public function show(Trip $trip)
    {
        $user = auth()->user();

        $trip->load(['driver', 'vehicle', 'waypoints', 'bookings.passenger', 'chatMessages.sender']);
        $this->ratings->attachDriverRating($trip);
        DriverScore::attachLatestToTrips(collect([$trip]));
        $driverScore = DriverScore::forTrip($trip);

        $canStart = $trip->driver_id === $user->id && $trip->status->value === 'scheduled';
        $canComplete = $trip->driver_id === $user->id && $trip->status->value === 'active';
        $canCancelTrip = ($trip->driver_id === $user->id || $user->isAdmin())
            && ! in_array($trip->status->value, ['completed', 'cancelled'], true);

        $myBooking = $user->bookings()->where('trip_id', $trip->id)->whereIn('status', ['requested', 'confirmed', 'boarded'])->first();
        $isParticipant = $trip->isParticipant($user);
        $womenOnlyBlocked = $trip->women_only && $user->gender !== 'female';
        $myInterest = $trip->interests()->where('user_id', $user->id)->first();
        $interestCount = $trip->interests()->where('status', 'pending')->count();

        // The booking form is shown to anyone who can book — except free
        // volunteer rides, which are a verified-worker benefit (phone-only
        // riders would otherwise see a form the service rejects).
        $canBookForm = $user->canBook() && (! $trip->is_free_volunteer || $user->canBookBenefits());

        return view('trips.show', compact(
            'trip',
            'user',
            'driverScore',
            'canStart',
            'canComplete',
            'canCancelTrip',
            'myBooking',
            'isParticipant',
            'womenOnlyBlocked',
            'myInterest',
            'interestCount',
            'canBookForm',
        ));
    }

    /**
     * "I want this journey" — a soft supply signal that never touches seats or
     * money (section 2.2). Idempotent per (trip, user); upgrades to a real
     * booking (matched) when the passenger books.
     */
    public function registerInterest(Request $request, Trip $trip)
    {
        try {
            $this->trips->registerInterest($trip, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'You’re on the interest list. When the driver confirms, book from the trip page.');
    }

    public function start(Request $request, Trip $trip)
    {
        try {
            $this->trips->start($trip, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('trips.show', $trip)->with('status', 'Trip started. Live location now streaming.');
    }

    public function complete(Request $request, Trip $trip)
    {
        try {
            $this->trips->completeTrip($trip, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('trips.show', $trip)->with('status', 'Trip completed. Fares settled.');
    }

    public function cancel(Request $request, Trip $trip)
    {
        try {
            $this->trips->cancelTrip($trip, $request->user(), $request->input('reason'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('trips.show', $trip)->with('status', 'Trip cancelled. Passengers refunded.');
    }

    public function storeMessage(Request $request, Trip $trip)
    {
        if (! $trip->isParticipant($request->user())) {
            return response()->json(['message' => 'Only trip participants can chat.'], 403);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = $trip->chatMessages()->create([
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        broadcast(new NewChatMessage($message->load('sender')));

        return response()->json([
            'chat' => [
                'id' => $message->id,
                'trip_id' => $message->trip_id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name,
                'message' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function publishRules(): array
    {
        return [
            'corridor' => ['required', Rule::enum(Corridor::class)],
            'origin_text' => ['required', 'string', 'max:255'],
            'destination_text' => ['required', 'string', 'max:255'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:60'],
            'departure_time' => ['required', 'date', 'after:now'],
            'is_free_volunteer' => ['sometimes', 'boolean'],
            'women_only' => ['sometimes', 'boolean'],
            'current_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'current_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'waypoints' => ['nullable', 'array'],
            'waypoints.*.label' => ['required_with:waypoints', 'string', 'max:255'],
            'waypoints.*.lat' => ['required_with:waypoints', 'numeric', 'between:-90,90'],
            'waypoints.*.lng' => ['required_with:waypoints', 'numeric', 'between:-180,180'],
        ];
    }
}

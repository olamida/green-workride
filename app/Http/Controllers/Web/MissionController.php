<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Services\MissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Rider-facing Promoted Activities hub ("Missions").
 *
 * Promoters define activities + rewards; the app tracks progress from real
 * events (auto) or photo proof (proof) and pays out — see MissionService.
 */
class MissionController extends Controller
{
    public function index(Request $request, MissionService $missions)
    {
        $user = $request->user();

        return view('missions.index', [
            'enabled' => (bool) config('workride.missions.enabled', false),
            'missions' => $missions->activeFor($user),
            'awards' => $missions->myAwards($user),
        ]);
    }

    public function submitProof(Request $request, Mission $mission, MissionService $missions)
    {
        $data = $request->validate([
            'proof_photo' => ['required', 'image', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        try {
            $submission = $missions->submitProof($request->user(), $mission, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('status', 'Proof submitted. The promoter will review it and the reward pays out on approval.');
    }
}

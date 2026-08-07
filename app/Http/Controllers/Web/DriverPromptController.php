<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DriverPrompt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Driver-facing demand prompts (gallery "service planning" Phase 3): a driver
 * accepts or dismisses a "N people want this corridor" nudge. Owner-only, and
 * accepting simply hands the driver into the publish form pre-selected to that
 * corridor — the demand signal becomes supply without any gated privilege.
 */
class DriverPromptController extends Controller
{
    public function accept(Request $request, DriverPrompt $prompt): RedirectResponse
    {
        $this->assertOwner($prompt, $request->user());

        $prompt->accept();

        return redirect()->route('trips.create', ['corridor' => $prompt->corridor->value])
            ->with('status', "{$prompt->people_count} people want {$prompt->corridor->label()} — publish to fill the gap.");
    }

    public function dismiss(Request $request, DriverPrompt $prompt): RedirectResponse
    {
        $this->assertOwner($prompt, $request->user());

        $prompt->dismiss();

        return back()->with('status', 'Prompt dismissed.');
    }

    private function assertOwner(DriverPrompt $prompt, ?User $user): void
    {
        if (! $user || $prompt->driver_id !== $user->id) {
            abort(403, 'This prompt is not yours.');
        }
    }
}

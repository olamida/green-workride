<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Rider profile — safety pack (emergency contact) + women-only preference.
 * The preference is never a hard sort; it only defaults the board filter.
 */
class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'unspecified'])],
            'prefers_women_only' => ['sometimes', 'boolean'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Profile updated.');
    }
}

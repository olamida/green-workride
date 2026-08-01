<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('workplace')
            ->when($request->query('search'), function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->query('level') !== null, function ($query) use ($request) {
                $query->where('verification_level', $request->query('level'));
            })
            ->latest()
            ->paginate(25);

        return view('admin.users', compact('users'));
    }

    public function ban(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 403, 'Admins cannot be banned.');

        $user->update(['is_banned' => true]);

        return back()->with('status', "{$user->name} has been banned.");
    }

    public function unban(Request $request, User $user)
    {
        $user->update(['is_banned' => false]);

        return back()->with('status', "{$user->name} has been reinstated.");
    }
}

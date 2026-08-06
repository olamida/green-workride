<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Admin "view as" role switcher (navigation-first sprint 1).
 *
 * The switch is DISPLAY-ONLY: it never mutates the persisted role and never
 * weakens security gates. The `admin` middleware + EnsureAdmin keep reading
 * the real `User::role`; the effective role only decides which UI an admin
 * sees (passenger-style nav emphasis + a "viewing as" banner).
 */
class RoleSwitcherService
{
    /** Roles an admin may view-as. Admin-only cases are never selectable. */
    public const VIEWABLE_ROLES = ['passenger', 'driver', 'both'];

    public function switch(User $admin, string $targetRole): void
    {
        abort_unless($admin->isAdmin(), 403, 'Role switching is restricted to admins.');

        if (in_array($targetRole, self::VIEWABLE_ROLES, true)) {
            session(['view_as_role' => $targetRole]);

            return;
        }

        $this->reset($admin);
    }

    public function reset(User $admin): void
    {
        session()->forget('view_as_role');
    }

    public function effectiveRole(User $user): UserRole
    {
        if (! $user->isAdmin()) {
            return $user->role;
        }

        $target = session('view_as_role');

        return in_array($target, self::VIEWABLE_ROLES, true)
            ? UserRole::from($target)
            : $user->role;
    }

    public function isViewingAs(User $user): bool
    {
        return $user->isAdmin() && in_array(session('view_as_role'), self::VIEWABLE_ROLES, true);
    }
}

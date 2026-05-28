<?php

namespace Modules\Users\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    /** Roles an admin can assign through this UI (students self-register). */
    protected const ASSIGNABLE_ROLES = ['admin', 'staff'];

    public function index(Request $request): Response
    {
        $filter = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'in:admin,staff,super_admin'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
        ]);

        $query = User::query()
            ->with('roles:id,name')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin', 'staff']))
            ->orderByDesc('id');

        if (! empty($filter['q'])) {
            $q = $filter['q'];
            $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('mobile', 'like', "%{$q}%"));
        }
        if (! empty($filter['role'])) {
            $role = $filter['role'];
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }
        if (! empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        return Inertia::render('Admin/Users', [
            'users' => $query->paginate(25)->withQueryString(),
            'filter' => $filter,
            'assignable_roles' => self::ASSIGNABLE_ROLES,
            'current_user_id' => $request->user()->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        return back()->with('flash', ['success' => "User {$user->email} created with role {$data['role']}."]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->guardNotSuperAdminUnlessSelf($user, $request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email,'.$user->id],
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,mobile,'.$user->id],
            'role' => ['required', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        // Super admin role cannot be reassigned through this UI.
        if ($user->hasRole('super_admin')) {
            return back()->withErrors(['role' => 'Super admin role cannot be changed through this screen.']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'status' => $data['status'],
        ]);
        $user->syncRoles([$data['role']]);

        return back()->with('flash', ['success' => "User {$user->email} updated."]);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->guardNotSuperAdminUnlessSelf($user, $request);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('flash', ['success' => "Password reset for {$user->email}."]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardNotSuperAdminUnlessSelf($user, $request);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $email = $user->email;
        $user->delete();

        return back()->with('flash', ['success' => "User {$email} removed."]);
    }

    /**
     * Super admins are not editable from this screen — they're seeded via
     * SuperAdminSeeder and changes must go through the seeder or DB directly.
     */
    protected function guardNotSuperAdminUnlessSelf(User $user, Request $request): void
    {
        if ($user->hasRole('super_admin') && $user->id !== $request->user()->id) {
            abort(403, 'Super admin accounts cannot be modified here.');
        }
    }
}

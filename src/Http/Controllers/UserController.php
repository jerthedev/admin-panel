<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * User Management Controller
 * 
 * Handles admin user management including creating, updating,
 * and managing admin users and their permissions.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Controllers
 */
class UserController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request): Response
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        
        $query = $userModel::query();

        // Filter to admin users only
        $this->filterAdminUsers($query);

        // Apply search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $users = $query->paginate(25)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'search' => $search,
            'sort' => [
                'field' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Show the form for creating a new admin user.
     */
    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => $this->getAvailableRoles(),
        ]);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request): RedirectResponse
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:admin,super-admin',
        ]);

        $user = $userModel::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Assign role if using a role system
        if ($request->role && method_exists($user, 'assignRole')) {
            $user->assignRole($request->role);
        }

        return redirect()
            ->route('admin-panel.users.show', $user)
            ->with('success', 'Admin user created successfully.');
    }

    /**
     * Display the specified admin user.
     */
    public function show(Request $request, $id): Response
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $user = $userModel::findOrFail($id);

        // Check if user is admin
        if (! $this->isAdminUser($user)) {
            abort(404, 'Admin user not found.');
        }

        return Inertia::render('Users/Show', [
            'user' => $this->transformUser($user),
            'roles' => $this->getUserRoles($user),
            'permissions' => $this->getUserPermissions($user),
            'sessions' => $this->getUserSessions($user),
        ]);
    }

    /**
     * Show the form for editing the specified admin user.
     */
    public function edit($id): Response
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $user = $userModel::findOrFail($id);

        // Check if user is admin
        if (! $this->isAdminUser($user)) {
            abort(404, 'Admin user not found.');
        }

        return Inertia::render('Users/Edit', [
            'user' => $this->transformUser($user),
            'roles' => $this->getAvailableRoles(),
            'currentRoles' => $this->getUserRoles($user),
        ]);
    }

    /**
     * Update the specified admin user.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $user = $userModel::findOrFail($id);

        // Check if user is admin
        if (! $this->isAdminUser($user)) {
            abort(404, 'Admin user not found.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|string|in:admin,super-admin',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $user->update($data);

        // Update role if using a role system
        if ($request->has('role') && method_exists($user, 'syncRoles')) {
            $user->syncRoles($request->role ? [$request->role] : []);
        }

        return redirect()
            ->route('admin-panel.users.show', $user)
            ->with('success', 'Admin user updated successfully.');
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy($id): RedirectResponse
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $user = $userModel::findOrFail($id);

        // Check if user is admin
        if (! $this->isAdminUser($user)) {
            abort(404, 'Admin user not found.');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin-panel.users.index')
            ->with('success', 'Admin user deleted successfully.');
    }

    /**
     * Filter query to admin users only.
     */
    protected function filterAdminUsers($query): void
    {
        // Check for is_admin field
        if (\Schema::hasColumn('users', 'is_admin')) {
            $query->where('is_admin', true);
            return;
        }

        // Check for admin_access field
        if (\Schema::hasColumn('users', 'admin_access')) {
            $query->where('admin_access', true);
            return;
        }

        // If using roles, filter by admin roles
        if (method_exists($query->getModel(), 'hasRole')) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin']);
            });
        }
    }

    /**
     * Check if user is an admin user.
     */
    protected function isAdminUser($user): bool
    {
        if (isset($user->is_admin)) {
            return (bool) $user->is_admin;
        }

        if (isset($user->admin_access)) {
            return (bool) $user->admin_access;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'super-admin']);
        }

        return false;
    }

    /**
     * Transform user for frontend.
     */
    protected function transformUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active ?? true,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ];
    }

    /**
     * Get available roles.
     */
    protected function getAvailableRoles(): array
    {
        return [
            'admin' => 'Administrator',
            'super-admin' => 'Super Administrator',
        ];
    }

    /**
     * Get user roles.
     */
    protected function getUserRoles($user): array
    {
        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->toArray();
        }

        return [];
    }

    /**
     * Get user permissions.
     */
    protected function getUserPermissions($user): array
    {
        if (method_exists($user, 'getAllPermissions')) {
            return $user->getAllPermissions()->pluck('name')->toArray();
        }

        return [];
    }

    /**
     * Get user sessions.
     */
    protected function getUserSessions($user): array
    {
        // This would integrate with session storage
        // For now, return empty array
        return [];
    }
}

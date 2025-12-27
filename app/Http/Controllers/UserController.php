<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'name');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $cursor = $request->input('cursor');
        $roleId = $request->input('role_id');

        $allowedSorts = ['name', 'email', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $query = User::query()
            ->with('role')
            ->withCount('productions')
            ->orderBy($sort, $direction)
            ->orderBy('id', 'asc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($roleId) {
            $query->where('role_id', $roleId);
        }

        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $data = collect($paginator->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $user->role?->name ?? 'No Role',
                'role_slug' => $user->role?->slug ?? null,
                'productions_count' => $user->productions_count ?? 0,
                'created_at' => $user->created_at->toDateString(),
            ];
        })->all();

        $roles = Role::all(['role_id', 'name', 'slug']);

        return Inertia::render('admin/Users/Index', [
            'users' => [
                'data' => $data,
                'next_cursor' => $paginator->nextCursor()?->encode() ?? null,
                'prev_cursor' => $paginator->previousCursor()?->encode() ?? null,
            ],
            'roles' => $roles,
            'meta' => [
                'sort' => $sort,
                'direction' => $direction,
                'q' => $q,
                'per_page' => $perPage,
                'role_id' => $roleId,
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all(['role_id', 'name', 'slug']);
        $productions = Production::where('status', 'active')
            ->orderBy('production_name')
            ->get(['production_id', 'production_name']);

        return Inertia::render('admin/Users/Create', [
            'roles' => $roles,
            'productions' => $productions,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => ['required', 'exists:roles,role_id'],
            'production_ids' => ['nullable', 'array'],
            'production_ids.*' => ['integer', 'exists:productions,production_id'],
            'can_access_glue_spreaders' => ['boolean'],
            'can_access_warehouse' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'can_access_glue_spreaders' => $data['can_access_glue_spreaders'] ?? false,
            'can_access_warehouse' => $data['can_access_warehouse'] ?? false,
        ]);

        // Attach productions if staff role
        $role = Role::find($data['role_id']);
        if ($role && $role->slug === Role::STAFF && ! empty($data['production_ids'])) {
            $user->productions()->sync($data['production_ids']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['role', 'productions']);

        return Inertia::render('admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $user->role?->name ?? 'No Role',
                'role_slug' => $user->role?->slug ?? null,
                'created_at' => $user->created_at->toDateTimeString(),
                'productions' => $user->productions->map(fn ($p) => [
                    'production_id' => $p->production_id,
                    'production_name' => $p->production_name,
                ])->all(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load(['role', 'productions']);

        $roles = Role::all(['role_id', 'name', 'slug']);
        $productions = Production::where('status', 'active')
            ->orderBy('production_name')
            ->get(['production_id', 'production_name']);

        return Inertia::render('admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_slug' => $user->role?->slug ?? null,
                'production_ids' => $user->productions->pluck('production_id')->toArray(),
                'can_access_glue_spreaders' => $user->can_access_glue_spreaders,
                'can_access_warehouse' => $user->can_access_warehouse,
            ],
            'roles' => $roles,
            'productions' => $productions,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_id' => ['required', 'exists:roles,role_id'],
            'production_ids' => ['nullable', 'array'],
            'production_ids.*' => ['integer', 'exists:productions,production_id'],
            'can_access_glue_spreaders' => ['boolean'],
            'can_access_warehouse' => ['boolean'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role_id = $data['role_id'];
        $user->can_access_glue_spreaders = $data['can_access_glue_spreaders'] ?? false;
        $user->can_access_warehouse = $data['can_access_warehouse'] ?? false;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        // Sync productions based on role
        $role = Role::find($data['role_id']);
        if ($role && $role->slug === Role::STAFF) {
            $user->productions()->sync($data['production_ids'] ?? []);
        } else {
            // Super users don't need production assignments
            $user->productions()->detach();
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->productions()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}

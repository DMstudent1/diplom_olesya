<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $users = User::select('id', 'name', 'email', 'phone', 'created_at', 'updated_at')
            ->with('roles')
            ->orderBy('id')
            ->cursorPaginate($perPage);

        return response()->json($users);
    }

    private function query()
    {
        return User::query();
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if (!$request->has('search')) {
            return;
        }

        $search = $request->input('search');

        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        if (empty($search)) {
            return;
        }
        $query->where(function (Builder $query) use ($search) {
            $query->where('users.name', 'LIKE', "%{$search}%") // исправляем child.name на users.name
                ->orWhere('users.email', 'LIKE', "%{$search}%")
                ->orWhere('users.phone', 'LIKE', "%{$search}%");
        });
    }

    public function getDataTable(Request $request)
    {
        return DataTables::eloquent($this->query())
            ->with('roles')
            ->setTransformer(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            })
            ->filter(function (Builder $query) use ($request) {
                $this->applyFilters($query, $request);
            })->toJson();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);
        $user->syncRoles($validated['roles']);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно создан',
            'user' => $user->load('roles')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        $user->syncRoles($validated['roles']);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно обновлен',
            'user' => $user->load('roles')
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Не даем удалить самого себя
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить самого себя'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно удален'
        ]);
    }

    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                ];
            }),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:6'
        ]);
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Пароль успешно сброшен'
        ]);
    }

    public function getRoles()
    {
        $roles = app('laratrust')->getRoles();

        return response()->json([
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                ];
            })
        ]);
    }
}

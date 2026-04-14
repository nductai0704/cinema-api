<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $managerRole = Role::where('role_name', User::ROLE_MANAGER)->first();
        $users = User::with(['role', 'cinema'])
            ->when($managerRole, fn($query) => $query->where('role_id', $managerRole->role_id))
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'cinema_id' => 'required|integer|exists:cinemas,cinema_id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $managerRoleId = Role::where('role_name', User::ROLE_MANAGER)->value('role_id');

        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);
        $data['role_id'] = $managerRoleId;
        $data['status'] = $data['status'] ?? 'active';

        $user = User::create($data);

        return response()->json($user->load('role', 'cinema'), 201);
    }

    public function show(int $manager_id)
    {
        $managerRoleId = Role::where('role_name', User::ROLE_MANAGER)->value('role_id');
        $user = User::with(['role', 'cinema'])
            ->where('role_id', $managerRoleId)
            ->findOrFail($manager_id);

        return response()->json($user);
    }

    public function update(Request $request, int $manager_id)
    {
        $managerRoleId = Role::where('role_name', User::ROLE_MANAGER)->value('role_id');
        $user = User::where('role_id', $managerRoleId)->findOrFail($manager_id);

        $data = $request->validate([
            'username' => 'sometimes|string|unique:users,username,' . $user->user_id . ',user_id',
            'password' => 'sometimes|string|min:6',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'cinema_id' => 'nullable|integer|exists:cinemas,cinema_id',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (! empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh()->load('role', 'cinema'));
    }

    public function destroy(int $manager_id)
    {
        $managerRoleId = Role::where('role_name', User::ROLE_MANAGER)->value('role_id');
        $user = User::where('role_id', $managerRoleId)->findOrFail($manager_id);
        $user->delete();

        return response()->json(['message' => 'Manager đã được xóa.']);
    }
}

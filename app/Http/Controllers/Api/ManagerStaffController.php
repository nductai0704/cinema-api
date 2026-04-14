<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ManagerStaffController extends Controller
{
    public function index(Request $request)
    {
        $staffRoleId = Role::where('role_name', User::ROLE_STAFF)->value('role_id');
        $users = User::with(['role', 'cinema'])
            ->where('role_id', $staffRoleId)
            ->where('cinema_id', $request->user()->cinema_id)
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
            'status' => 'nullable|in:active,inactive',
        ]);

        $manager = $request->user();
        $staffRoleId = Role::where('role_name', User::ROLE_STAFF)->value('role_id');

        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);
        $data['role_id'] = $staffRoleId;
        $data['cinema_id'] = $manager->cinema_id;
        $data['status'] = $data['status'] ?? 'active';

        $user = User::create($data);

        return response()->json($user->load('role', 'cinema'), 201);
    }

    public function show(Request $request, int $staff_id)
    {
        $staffRoleId = Role::where('role_name', User::ROLE_STAFF)->value('role_id');
        $user = User::with(['role', 'cinema'])
            ->where('role_id', $staffRoleId)
            ->where('cinema_id', $request->user()->cinema_id)
            ->findOrFail($staff_id);

        return response()->json($user);
    }

    public function update(Request $request, int $staff_id)
    {
        $staffRoleId = Role::where('role_name', User::ROLE_STAFF)->value('role_id');
        $user = User::where('role_id', $staffRoleId)
            ->where('cinema_id', $request->user()->cinema_id)
            ->findOrFail($staff_id);

        $data = $request->validate([
            'username' => 'sometimes|string|unique:users,username,' . $user->user_id . ',user_id',
            'password' => 'sometimes|string|min:6',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (! empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh()->load('role', 'cinema'));
    }

    public function destroy(Request $request, int $staff_id)
    {
        $staffRoleId = Role::where('role_name', User::ROLE_STAFF)->value('role_id');
        $user = User::where('role_id', $staffRoleId)
            ->where('cinema_id', $request->user()->cinema_id)
            ->findOrFail($staff_id);

        $user->delete();

        return response()->json(['message' => 'Staff đã được xóa.']);
    }
}

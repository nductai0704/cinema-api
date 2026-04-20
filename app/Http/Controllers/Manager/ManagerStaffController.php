<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class ManagerStaffController extends Controller
{
    public function index()
    {
        // Trait autoscoped to current Manager's cinema. Only lists users of this cinema.
        // Also ensure we only select 'staff' just to be safe.
        $staffRole = Role::where('role_name', 'staff')->first();
        
        $staffs = User::where('role_id', $staffRole?->role_id)->paginate(15);
        return UserResource::collection($staffs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:100|unique:users',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        $staffRole = Role::where('role_name', 'staff')->first();
        
        $data['role_id'] = $staffRole->role_id;
        $data['password_hash'] = Hash::make($request->password);
        $data['status'] = 'active';

        // cinema_id is auto-assigned by BelongsToCinema Trait creating event
        $staff = User::create($data);

        return new UserResource($staff);
    }

    public function update(Request $request, $id)
    {
        $staff = User::findOrFail($id);

        $data = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->password);
        }

        $staff->update($data);

        return new UserResource($staff);
    }
}

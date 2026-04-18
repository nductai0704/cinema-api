<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\ManagerRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ManagerAccountController extends Controller
{
    /**
     * Display a listing of managers.
     */
    public function index()
    {
        $managerRole = Role::where('role_name', 'manager')->first();
        $managers = User::where('role_id', $managerRole?->role_id)
            ->with('cinema')
            ->paginate(15);
            
        return UserResource::collection($managers);
    }

    /**
     * Store a newly created manager.
     */
    public function store(ManagerRequest $request)
    {
        $managerRole = Role::where('role_name', 'manager')->first();
        
        $data = $request->validated();
        $data['role_id'] = $managerRole->role_id;
        $data['password_hash'] = Hash::make($request->password);
        $data['status'] = 'active';

        $user = User::create($data);

        return new UserResource($user->load('cinema'));
    }

    /**
     * Update the manager account.
     */
    public function update(ManagerRequest $request, User $manager)
    {
        $data = $request->validated();
        
        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->password);
        }

        $manager->update($data);

        return new UserResource($manager->load('cinema'));
    }

    /**
     * Toggle lock/unlock status of manager account.
     */
    public function toggleStatus(User $manager)
    {
        $newStatus = $manager->status === 'active' ? 'inactive' : 'active';
        $manager->update(['status' => $newStatus]);

        return response()->json([
            'message' => $newStatus === 'active' ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản thành công.',
            'status' => $newStatus
        ]);
    }
}

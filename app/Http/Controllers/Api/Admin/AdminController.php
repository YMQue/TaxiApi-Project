<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => Admin::where('role', 'support')->paginate(15),
        ]);
    }

    public function store(StoreAdminRequest $request)
    {
        $data = $request->validated();

        $admin = Admin::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => $data['password'], // hashed by the cast
            'role'        => 'support',           // always support
            'permissions' => array_values(array_unique($data['permissions'] ?? [])),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'support admin created',
            'data'    => $admin,
        ], 201);
    }

    public function update(UpdateAdminRequest $request, Admin $admin)
    {
        abort_if($admin->isSuper(), 403, 'super admins cannot be edited here');

        $data = $request->validated();

        if (isset($data['permissions'])) {
            $data['permissions'] = array_values(array_unique($data['permissions']));
        }

        $admin->update($data);

        return response()->json([
            'success' => true,
            'message' => 'admin updated',
            'data'    => $admin->refresh(),
        ]);
    }

    public function destroy(Admin $admin)
    {
        abort_if($admin->isSuper(), 403, 'super admins cannot be deleted here');

        $admin->tokens()->delete(); // revoke active sessions
        $admin->delete();

        return response()->json(['success' => true, 'message' => 'admin deleted']);
    }
}

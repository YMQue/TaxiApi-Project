<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user('admin');

        if (! $admin instanceof Admin) {
            return response()->json(['success' => false, 'message' => 'not allowed for you'], 401);
        }

        if (! in_array($admin->role, $roles, true)) {
            return response()->json(['success' => false, 'message' => 'you dont have the right role'], 403);
        }

        return $next($request);
    }
}
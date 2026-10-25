<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
     public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = $request->user();

        if (! $admin instanceof Admin) {
            return response()->json(['success' => false, 'message' => 'not allowed for you'], 401);
        }

        if (! $admin->hasAnyPermission(...$permissions)) {
            return response()->json(['success' => false, 'message' => 'you dont have permission to do this'], 403);
        }

        return $next($request);
    }
}

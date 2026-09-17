<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
     public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
 
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'not allowed for you',
            ], 401);
        }
 
        foreach ($roles as $role) {
            if ($this->userHasRole($user, $role)) {
                return $next($request);
            }
        }
 
        return response()->json([
            'success' => false,
            'message' => 'you dont have the right role'
        ], 403);
    }
 
    private function userHasRole($user, string $role): bool
    {
        return match ($role) {
            'driver' => $user->isDriver(),
            'rider'  => !$user->isDriver(), // أي حساب مش سائق بيتعامل معاه كراكب
            default  => false,
        };
    }
}

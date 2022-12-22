<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Authenticate 
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Please log in first."
            ], 401);
        }
        return $next($request);
    }
}

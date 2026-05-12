<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyDemoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $user = $request->user('admin');

        if ($user && $user->email === 'demo@malfalah.com') {
            return response()->json([
                'message' => 'Demo Mode: You do not have permission to modify data. Please login as a Super Admin to make changes.'
            ], 403);
        }

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;

class Operator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->user()->role == 'operator') {
            return $next($request);
        } else {
            return response()->json([
                'error' => false,
                'message' => 'You don\'t have operator access.',
                'data' => null
            ]);
        }
    }
}

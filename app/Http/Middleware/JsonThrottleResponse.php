<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class JsonThrottleResponse
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (ThrottleRequestsException $e) {
            $headers = method_exists($e, 'getHeaders') ? $e->getHeaders() : [];
            $seconds = (int) ($headers['Retry-After'] ?? 60);

            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                'message_type' => 'error',
            ], 429)->withHeaders([
                'Retry-After' => $seconds,
            ]);
        }
    }
}

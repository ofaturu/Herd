<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request for Postman / automated tooling API key protection.
     * Note: API Key header is strictly for server-to-server / Postman tooling access.
     * User authentication must use Sanctum session/token.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-API-KEY');
        $configuredKey = config('services.api_key');

        if (! $configuredKey || ! is_string($providedKey) || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: API Key tidak valid atau tidak disertakan.',
            ], 401);
        }

        return $next($request);
    }
}

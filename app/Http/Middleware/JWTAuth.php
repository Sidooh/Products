<?php

namespace App\Http\Middleware;

use App\Helpers\JWT;
use App\Traits\ApiResponse;
use Cache;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JWTAuth
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return JsonResponse
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next): JsonResponse|Response
    {
        $bearer = $request->bearerToken();

        if (! JWT::verify($bearer)) {
            throw new AuthenticationException();
        }

        Cache::put('auth_token', $bearer, JWT::expiry($bearer)->diffInMinutes());

        return $next($request);
    }
}

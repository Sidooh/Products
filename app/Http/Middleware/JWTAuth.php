<?php

namespace App\Http\Middleware;

use App\Library\JWT;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JWTAuth
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        $bearer = $request->bearerToken();

        if(!JWT::verify($bearer)) return response()->json([
            'status'  => 'error',
            'message' => "This action is unauthorized!",
        ], 401);

        return $next($request);
    }
}

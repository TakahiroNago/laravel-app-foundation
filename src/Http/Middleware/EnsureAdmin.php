<?php

namespace TakahiroNago\AppFoundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TakahiroNago\AppFoundation\Contracts\Administrator;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof Administrator && $user->isAdmin(), 403);

        return $next($request);
    }
}

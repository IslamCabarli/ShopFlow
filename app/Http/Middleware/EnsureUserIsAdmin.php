<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Traits\ApiResponse;

class EnsureUserIsAdmin
{
    use ApiResponse;
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if( $request->user()?->role === 'admin' ) {return $next($request);}
        else { return  $this->error('Forbidden. Admin access required.', 403);}

    }
}

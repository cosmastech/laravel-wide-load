<?php

namespace Cosmastech\WideLoad\Http\Middleware;

use Closure;
use Cosmastech\WideLoad\WideLoad;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WideLoadMiddleware
{
    public function __construct(
        protected readonly WideLoad $wideLoad,
    ) {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->wideLoad->report()->flush();
    }
}

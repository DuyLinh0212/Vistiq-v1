<?php

namespace App\Http\Middleware;

use App\Services\FrontendContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyFrontendGateway
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(FrontendContext::class)->isTrustedGateway($request), 403, 'Frontend gateway authentication required.');

        return $next($request);
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Request;

class FrontendContext
{
    public function isTrustedGateway(Request $request): bool
    {
        $key = (string) config('frontends.gateway_key');

        return $key !== '' && hash_equals($key, (string) $request->header('X-Vistiq-Gateway-Key'));
    }

    public function apiPath(string $path): string
    {
        if (str_starts_with($path, '/api/') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        if (app('vistiq.surface') === 'admin') {
            return '/api/v1'.(preg_match('#^/admin(?:/|$)#', $path) ? $path : '/admin/auth'.$path);
        }

        return '/api/v1'.$path;
    }
}

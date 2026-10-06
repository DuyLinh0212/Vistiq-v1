<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class FrontendProxyController
{
    public function __invoke(Request $request, ApiClient $api): Response
    {
        try {
            $upstream = $api->forward($request);
        } catch (ConnectionException|RuntimeException $exception) {
            report($exception);

            return $request->expectsJson()
                ? response()->json(['message' => 'Không kết nối được Vistiq.Api.'], 502)
                : response('Không kết nối được Vistiq.Api. Vui lòng chạy backend và thử lại.', 502)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $response = response($upstream->body(), $upstream->status());
        $hopHeaders = array_map('trim', explode(',', strtolower($upstream->header('Connection'))));
        $excluded = ['connection', 'keep-alive', 'transfer-encoding', 'te', 'trailer', 'upgrade', 'proxy-authenticate', 'proxy-authorization', 'content-length', 'content-encoding', ...$hopHeaders];
        foreach ($upstream->headers() as $name => $values) {
            if (! in_array(strtolower($name), $excluded, true)) {
                $response->headers->set($name, $values);
            }
        }

        return $response;
    }
}

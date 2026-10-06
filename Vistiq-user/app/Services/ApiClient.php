<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiClient
{
    public function forward(Request $request): Response
    {
        if (blank(config('backend.gateway_key'))) {
            throw new RuntimeException('VISTIQ_GATEWAY_KEY is not configured.');
        }

        $headers = [];
        foreach (['Accept', 'Accept-Language', 'Cookie', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN', 'Origin', 'Referer', 'Range', 'If-None-Match', 'If-Modified-Since'] as $name) {
            if ($request->headers->has($name)) {
                $headers[$name] = $request->header($name);
            }
        }
        $headers['X-Vistiq-Gateway-Key'] = config('backend.gateway_key');
        $headers['X-Vistiq-Surface'] = config('backend.surface');
        $headers['X-Vistiq-Client-IP'] = $request->ip();
        $client = Http::withHeaders($headers)->withoutRedirecting()
            ->connectTimeout(5)->timeout(config('backend.timeout'));
        $url = rtrim(config('backend.url'), '/').$request->getRequestUri();
        $handles = [];

        try {
            if ($request->allFiles() !== []) {
                $parts = $this->formParts($request->request->all());
                foreach ($this->files($request->allFiles()) as $name => $file) {
                    $handle = fopen($file->getRealPath(), 'rb');
                    if ($handle === false) {
                        throw new RuntimeException('Cannot read the uploaded file.');
                    }
                    $handles[] = $handle;
                    $parts[] = ['name' => $name, 'contents' => $handle, 'filename' => $file->getClientOriginalName(), 'headers' => ['Content-Type' => $file->getMimeType() ?: 'application/octet-stream']];
                }

                return $client->asMultipart()->send($request->method(), $url, ['multipart' => $parts]);
            }

            return $client->withBody($request->getContent(), $request->header('Content-Type', 'application/octet-stream'))
                ->send($request->method(), $url);
        } finally {
            foreach ($handles as $handle) {
                fclose($handle);
            }
        }
    }

    /** @return array<int, array{name: string, contents: string}> */
    private function formParts(array $values, string $prefix = ''): array
    {
        $parts = [];
        foreach ($values as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';
            if (is_array($value)) {
                $parts = [...$parts, ...$this->formParts($value, $name)];
            } else {
                $parts[] = ['name' => $name, 'contents' => (string) $value];
            }
        }

        return $parts;
    }

    /** @return array<string, UploadedFile> */
    private function files(array $files, string $prefix = ''): array
    {
        $result = [];
        foreach ($files as $key => $file) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';
            if (is_array($file)) {
                $result += $this->files($file, $name);
            } elseif ($file instanceof UploadedFile) {
                $result[$name] = $file;
            }
        }

        return $result;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SudregApiClient
{
    public function isConfigured(): bool
    {
        $id = config('sudreg.client_id');
        $secret = config('sudreg.client_secret');

        return is_string($id) && $id !== ''
            && is_string($secret) && $secret !== '';
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|list<mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $response = $this->request($path, $query);

        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey());
            $response = $this->request($path, $query);
        }

        if ($response->status() === 400 || $response->status() === 404) {
            return [];
        }

        try {
            $response->throw();
        } catch (RequestException $exception) {
            Log::warning('Sudski registar nije vratio podatke.', [
                'path' => $path,
                'status' => $exception->response?->status(),
                'log_id' => $exception->response?->header('X-Log-Id'),
            ]);

            throw $exception;
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function request(string $path, array $query)
    {
        $url = rtrim((string) config('sudreg.base_url'), '/').'/'.ltrim($path, '/');

        return Http::acceptJson()
            ->timeout((int) config('sudreg.timeout', 8))
            ->withToken($this->token())
            ->get($url, $query + [
                'no_data_error' => '0',
                'omit_nulls' => '1',
            ]);
    }

    private function token(): string
    {
        $cached = Cache::get($this->tokenCacheKey());
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asForm()
            ->timeout((int) config('sudreg.timeout', 8))
            ->post((string) config('sudreg.token_url'), [
                'grant_type' => 'client_credentials',
                'client_id' => (string) config('sudreg.client_id'),
                'client_secret' => (string) config('sudreg.client_secret'),
            ])
            ->throw();

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Sudski registar nije vratio pristupni token.');
        }

        $ttl = max(60, (int) $response->json('expires_in', 21600) - 60);
        Cache::put($this->tokenCacheKey(), $token, $ttl);

        return $token;
    }

    private function tokenCacheKey(): string
    {
        return 'sudreg.oauth_token';
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
use RuntimeException;

class FirebaseServiceAccountTokenProvider
{
    private ?string $cachedToken = null;

    private int $cachedTokenExpiresAt = 0;

    public function getAccessToken(): ?string
    {
        if ($this->cachedToken !== null && time() < $this->cachedTokenExpiresAt - 60) {
            return $this->cachedToken;
        }

        $credentials = $this->resolveCredentials();

        if ($credentials === null) {
            return null;
        }

        $jwt = $this->createSignedJwt($credentials);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || trim($accessToken) === '') {
            return null;
        }

        $this->cachedToken = $accessToken;
        $this->cachedTokenExpiresAt = time() + (int) $response->json('expires_in', 3600);

        return $this->cachedToken;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCredentials(): ?array
    {
        $path = config('services.firebase.credentials');

        if (! is_string($path) || trim($path) === '' || ! is_readable($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return null;
        }

        $email = $decoded['client_email'] ?? null;
        $privateKey = $decoded['private_key'] ?? null;

        if (! is_string($email) || trim($email) === '' || ! is_string($privateKey) || trim($privateKey) === '') {
            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function createSignedJwt(array $credentials): string
    {
        $header = $this->base64UrlEncode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claims = $this->base64UrlEncode((string) json_encode([
            'iss' => $credentials['client_email'],
            'sub' => $credentials['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/identitytoolkit',
        ]));

        $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);

        if (! $privateKey instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('Chave privada do Firebase invalida.');
        }

        $data = "{$header}.{$claims}";
        $signature = '';

        if (! openssl_sign($data, $signature, $privateKey, \OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Nao foi possivel assinar o token de servico do Firebase.');
        }

        return $data.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

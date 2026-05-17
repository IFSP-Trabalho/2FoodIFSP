<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Kreait\Laravel\Firebase\Facades\Firebase;
use RuntimeException;
use Throwable;

class FirebaseUserProvisioningService
{
    public function __construct(
        private readonly FirebaseServiceAccountTokenProvider $serviceAccountTokenProvider
    ) {
    }

    /**
     * Creates a Firebase Auth user and returns its UID.
     */
    public function provision(string $username, string $email, string $password): string
    {
        if (class_exists(Firebase::class)) {
            try {
                $createdUser = Firebase::auth()->createUser([
                    'displayName' => $username,
                    'email' => $email,
                    'emailVerified' => false,
                    'password' => $password,
                    'disabled' => false,
                ]);

                return $createdUser->uid;
            } catch (Throwable) {
                // Falls back to Identity Toolkit REST API.
            }
        }

        $apiKey = config('services.firebase.web_api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('FIREBASE_WEB_API_KEY nao configurada para criar usuarios.');
        }

        $response = Http::post(
            'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key='.$apiKey,
            [
                'email' => $email,
                'password' => $password,
                'displayName' => $username,
                'returnSecureToken' => false,
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException('Nao foi possivel criar o usuario no Firebase Auth.');
        }

        $uid = data_get($response->json(), 'localId');

        if (! is_string($uid) || trim($uid) === '') {
            throw new RuntimeException('UID nao retornado pelo Firebase Auth.');
        }

        return $uid;
    }

    /**
     * Disable an existing Firebase Auth user (best-effort).
     */
    public function disableUser(string $uid): bool
    {
        if (class_exists(Firebase::class)) {
            try {
                Firebase::auth()->updateUser($uid, ['disabled' => true]);

                return true;
            } catch (Throwable) {
                // Falls back to service-account REST below.
            }
        }

        return $this->disableUserViaServiceAccountRest($uid);
    }

    /**
     * Updates display name, email and optionally password for an existing Firebase Auth user.
     *
     * @throws RuntimeException When Firebase update is unavailable or fails.
     */
    public function updateCredentials(
        string $uid,
        string $displayName,
        string $email,
        ?string $password = null
    ): void {
        if (class_exists(Firebase::class)) {
            try {
                $this->updateCredentialsViaAdminSdk($uid, $displayName, $email, $password);

                return;
            } catch (Throwable) {
                // Falls back to service-account REST below.
            }
        }

        $this->updateCredentialsViaServiceAccountRest($uid, $displayName, $email, $password);
    }

    /**
     * Best-effort display name sync when only the local name changes.
     */
    public function tryUpdateDisplayName(string $uid, string $displayName, string $currentEmail): void
    {
        try {
            $this->updateCredentials($uid, $displayName, $currentEmail, null);
        } catch (Throwable) {
            // Ignored: login does not depend on displayName.
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function updateCredentialsViaAdminSdk(
        string $uid,
        string $displayName,
        string $email,
        ?string $password
    ): void {
        $properties = [
            'displayName' => $displayName,
            'email' => $email,
        ];

        if ($password !== null && $password !== '') {
            $properties['password'] = $password;
        }

        Firebase::auth()->updateUser($uid, $properties);
    }

    private function updateCredentialsViaServiceAccountRest(
        string $uid,
        string $displayName,
        string $email,
        ?string $password
    ): void {
        $accessToken = $this->serviceAccountTokenProvider->getAccessToken();

        if ($accessToken === null) {
            throw new RuntimeException(
                'Configure FIREBASE_CREDENTIALS no .env com o JSON da conta de servico do Firebase para alterar e-mail ou senha.'
            );
        }

        $apiKey = config('services.firebase.web_api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('FIREBASE_WEB_API_KEY nao configurada.');
        }

        $payload = array_filter([
            'localId' => $uid,
            'displayName' => $displayName,
            'email' => $email,
            'password' => ($password !== null && $password !== '') ? $password : null,
            'returnSecureToken' => false,
        ], static fn ($value) => $value !== null);

        $response = Http::withToken($accessToken)->post(
            'https://identitytoolkit.googleapis.com/v1/accounts:update?key='.$apiKey,
            $payload
        );

        if ($response->successful()) {
            return;
        }

        $errorCode = (string) data_get($response->json(), 'error.message', '');

        throw new RuntimeException($this->mapRestErrorMessage($errorCode));
    }

    private function disableUserViaServiceAccountRest(string $uid): bool
    {
        $accessToken = $this->serviceAccountTokenProvider->getAccessToken();

        if ($accessToken === null) {
            return false;
        }

        $apiKey = config('services.firebase.web_api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return false;
        }

        $response = Http::withToken($accessToken)->post(
            'https://identitytoolkit.googleapis.com/v1/accounts:update?key='.$apiKey,
            [
                'localId' => $uid,
                'disableUser' => true,
                'returnSecureToken' => false,
            ]
        );

        return $response->successful();
    }

    private function mapRestErrorMessage(string $errorCode): string
    {
        return match ($errorCode) {
            'EMAIL_EXISTS' => 'Este e-mail ja esta em uso por outra conta no Firebase.',
            'INVALID_EMAIL' => 'E-mail invalido para o Firebase.',
            'WEAK_PASSWORD' => 'Senha muito fraca. Use pelo menos 6 caracteres.',
            'INVALID_PASSWORD' => 'Senha invalida para o Firebase.',
            'USER_NOT_FOUND', 'INVALID_LOCAL_ID' => 'Usuario nao encontrado no Firebase Auth.',
            default => 'Falha ao atualizar conta no Firebase. Tente novamente.',
        };
    }
}

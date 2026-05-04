<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Kreait\Laravel\Firebase\Facades\Firebase;
use RuntimeException;
use Throwable;

class FirebaseUserProvisioningService
{
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
     * Returns true on success, false if Firebase Admin SDK is unavailable or the call fails.
     * The caller should apply soft delete regardless of this result, since the local
     * deleted_at already prevents login.
     */
    public function disableUser(string $uid): bool
    {
        if (! class_exists(Firebase::class)) {
            return false;
        }

        try {
            Firebase::auth()->updateUser($uid, ['disabled' => true]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}

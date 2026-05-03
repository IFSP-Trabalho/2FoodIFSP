<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Kreait\Laravel\Firebase\Facades\Firebase;
use RuntimeException;
use Throwable;

class FirebaseTokenVerifier
{
    /**
     * Validate Firebase idToken and return UID.
     */
    public function verifyAndGetUid(string $idToken): string
    {
        if (class_exists(Firebase::class)) {
            try {
                $verifiedToken = Firebase::auth()->verifyIdToken($idToken);

                return (string) $verifiedToken->claims()->get('sub');
            } catch (Throwable) {
                // Falls back to REST verification.
            }
        }

        $apiKey = config('services.firebase.web_api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('FIREBASE_WEB_API_KEY is not configured.');
        }

        $response = Http::post(
            'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key='.$apiKey,
            ['idToken' => $idToken]
        );

        if (! $response->successful()) {
            throw new RuntimeException('Invalid Firebase token.');
        }

        $uid = data_get($response->json(), 'users.0.localId');

        if (! is_string($uid) || trim($uid) === '') {
            throw new RuntimeException('Firebase UID not found in token response.');
        }

        return $uid;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly FirebaseTokenVerifier $tokenVerifier
    ) {
    }

    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            return $this->redirectByRole($user->role);
        }

        return Inertia::render('Auth/Login');
    }

    public function firebaseLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idToken' => ['required', 'string'],
        ]);

        try {
            $uid = $this->tokenVerifier->verifyAndGetUid($validated['idToken']);
        } catch (Throwable) {
            return back()->withErrors([
                'email' => 'Token invalido. Tente novamente.',
            ]);
        }

        $user = User::find($uid);

        if (! $user instanceof User) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Usuario nao cadastrado no sistema.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user->role);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole(string $role): RedirectResponse
    {
        if ($role !== 'admin') {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Acesso permitido apenas para administradores.']);
        }

        return redirect()->route('admin.dashboard');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Cadastros/Users', [
            'users' => $this->getUsers(),
        ]);
    }

    public function departments(): Response
    {
        return Inertia::render('Admin/Cadastros/Departments', [
            'departments' => $this->getDepartments(),
        ]);
    }

    public function dishes(): Response
    {
        return Inertia::render('Admin/Cadastros/Dishes');
    }

    public function store(): RedirectResponse
    {
        // MVP: persistencia real sera implementada na fase 2.
        return back();
    }

    public function update(string $user): RedirectResponse
    {
        // MVP: persistencia real sera implementada na fase 2.
        return back();
    }

    public function destroy(string $user): RedirectResponse
    {
        // MVP: incluir validacao para proteger admin root na fase 2.
        return back();
    }

    public function syncDepartments(string $user): RedirectResponse
    {
        // MVP: sincronizacao real da pivot user_department fica para a fase 2.
        return back();
    }

    private function getUsers(): array
    {
        return [
            [
                'id' => 'u-001',
                'name' => 'Gabriel Henrique',
                'email' => 'gabriel@4foods.com',
                'departments' => ['Admin'],
                'is_root_admin' => true,
            ],
            [
                'id' => 'u-002',
                'name' => 'Ana Souza',
                'email' => 'ana@4foods.com',
                'departments' => ['Kitchen'],
                'is_root_admin' => false,
            ],
            [
                'id' => 'u-003',
                'name' => 'Carlos Mendes',
                'email' => 'carlos@4foods.com',
                'departments' => ['Financeiro', 'Admin'],
                'is_root_admin' => false,
            ],
        ];
    }

    private function getDepartments(): array
    {
        return ['Admin', 'Kitchen', 'Financeiro', 'Garcom'];
    }
}

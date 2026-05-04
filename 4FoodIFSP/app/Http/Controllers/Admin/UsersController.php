<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseUserProvisioningService;
use App\Services\UserCreationLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UsersController extends Controller
{
    public function __construct(
        private readonly FirebaseUserProvisioningService $firebaseUserProvisioningService,
        private readonly UserCreationLimitService $userCreationLimitService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Cadastros/Users', [
            'users' => $this->getUsers(),
            'departments' => $this->getDepartments(),
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

    public function store(Request $request): RedirectResponse
    {
        if (! $this->userCreationLimitService->canCreateUserByPlan()) {
            return back()
                ->withErrors(['user_limit' => $this->userCreationLimitService->getLimitErrorMessage()])
                ->withInput();
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:80', Rule::unique('users', 'name')],
            'department_id' => ['required', 'string', Rule::exists('departments', 'id')],
            'email' => ['required', 'email:rfc,dns', 'max:180', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6', 'max:80'],
        ]);

        try {
            $uid = $this->firebaseUserProvisioningService->provision(
                $validated['username'],
                $validated['email'],
                $validated['password']
            );
        } catch (Throwable) {
            return back()
                ->withErrors(['email' => 'Falha ao criar conta no Firebase. Tente novamente.'])
                ->withInput();
        }

        $departmentSlug = DB::table('departments')
            ->where('id', $validated['department_id'])
            ->value('slug');

        $role = $this->resolveRoleByDepartmentSlug(
            is_string($departmentSlug) ? $departmentSlug : ''
        );

        User::query()->create([
            'id' => $uid,
            'name' => $validated['username'],
            'email' => strtolower($validated['email']),
            'role' => $role,
            'department_id' => $validated['department_id'],
            'must_reset_password' => true,
        ]);

        return redirect()
            ->route('admin.cadastros.users.index')
            ->with('success', 'Usuario criado com sucesso.');
    }

    public function update(string $user): RedirectResponse
    {
        // MVP: persistencia real sera implementada na proxima fase.
        return back();
    }

    public function destroy(string $user): RedirectResponse
    {
        // MVP: incluir validacao para proteger admin root na proxima fase.
        return back();
    }

    public function syncDepartments(string $user): RedirectResponse
    {
        // MVP: sincronizacao real da pivot user_department fica para a fase 2.
        return back();
    }

    private function getUsers(): array
    {
        $rootAdminUid = trim((string) env('ADMIN_FIREBASE_UID', ''));

        return User::query()
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->orderByDesc('users.created_at')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'departments.name as department_name',
                'departments.slug as department_slug',
            ])
            ->map(function (User $user) use ($rootAdminUid): array {
                $departmentLabel = $this->formatDepartmentLabel(
                    (string) ($user->department_slug ?? ''),
                    (string) ($user->department_name ?? '')
                );

                return [
                    'id' => (string) $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'departments' => [$departmentLabel],
                    'is_root_admin' => $rootAdminUid !== '' && (string) $user->id === $rootAdminUid,
                ];
            })
            ->values()
            ->all();
    }

    private function getDepartments(): array
    {
        return DB::table('departments')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (object $department): array => [
                'id' => (string) $department->id,
                'name' => (string) $department->name,
                'slug' => (string) $department->slug,
                'label' => $this->formatDepartmentLabel(
                    (string) $department->slug,
                    (string) $department->name
                ),
            ])
            ->values()
            ->all();
    }

    private function resolveRoleByDepartmentSlug(string $slug): string
    {
        return match (strtolower(trim($slug))) {
            'admin' => 'admin',
            'kitchen' => 'kitchen',
            'finance' => 'finance',
            'waiter' => 'waiter',
            default => 'waiter',
        };
    }

    private function formatDepartmentLabel(string $slug, string $name): string
    {
        $fallback = trim($name);

        return match (strtolower(trim($slug))) {
            'admin' => 'Admin',
            'kitchen' => 'Kitchen',
            'finance' => 'Financeiro',
            'waiter' => 'Garcom',
            default => $fallback !== '' ? $fallback : 'Departamento',
        };
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseUserProvisioningService;
use App\Services\UserCreationLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
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
        return Inertia::render('Admin/Cadastros/Dishes', [
            'categories' => $this->getDishCategories(),
            'dishes' => $this->getDishes(),
        ]);
    }

    public function storeDish(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'category_id' => ['required', 'uuid', 'exists:dish_categories,id'],
            'active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Informe o nome do prato.',
            'price.required' => 'Informe um preço válido.',
            'price.min' => 'O preço deve ser maior que zero.',
            'category_id.required' => 'Selecione uma categoria.',
            'photo.image' => 'A foto deve ser JPG, PNG ou WebP (máx. 2 MB).',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('dishes', 'public');
        }

        DB::table('dishes')->insert([
            'id' => Str::uuid()->toString(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'photo_path' => $photoPath,
            'category_id' => $validated['category_id'],
            'active' => $request->boolean('active'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.cadastros.dishes.index')
            ->with('success', 'Prato cadastrado com sucesso.');
    }

    public function updateDish(Request $request, string $dish): RedirectResponse
    {
        $existing = DB::table('dishes')->where('id', $dish)->first();

        if ($existing === null) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'category_id' => ['required', 'uuid', 'exists:dish_categories,id'],
            'active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Informe o nome do prato.',
            'price.required' => 'Informe um preço válido.',
            'category_id.required' => 'Selecione uma categoria.',
            'photo.image' => 'A foto deve ser JPG, PNG ou WebP (máx. 2 MB).',
        ]);

        $photoPath = $existing->photo_path;

        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('dishes', 'public');
        }

        DB::table('dishes')
            ->where('id', $dish)
            ->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'photo_path' => $photoPath,
                'category_id' => $validated['category_id'],
                'active' => $request->boolean('active'),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.cadastros.dishes.index')
            ->with('success', 'Prato atualizado com sucesso.');
    }

    public function destroyDish(string $dish): RedirectResponse
    {
        $existing = DB::table('dishes')->where('id', $dish)->first();

        if ($existing === null) {
            abort(404);
        }

        $hasOrders = DB::table('order_items')
            ->where('dish_id', $dish)
            ->exists();

        if ($hasOrders) {
            return redirect()
                ->route('admin.cadastros.dishes.index')
                ->withErrors([
                    'delete' => 'Este prato nao pode ser excluido porque ja foi usado em pedidos.',
                ]);
        }

        if ($existing->photo_path) {
            Storage::disk('public')->delete($existing->photo_path);
        }

        DB::table('dishes')->where('id', $dish)->delete();

        return redirect()
            ->route('admin.cadastros.dishes.index')
            ->with('success', 'Prato excluido com sucesso.');
    }

    public function updateDepartmentColor(Request $request, string $department): RedirectResponse
    {
        $validated = $request->validate([
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $normalizedColor = strtoupper($validated['color']);

        $updated = DB::table('departments')
            ->where('id', $department)
            ->update(['color' => $normalizedColor]);

        if ($updated === 0) {
            abort(404);
        }

        return redirect()
            ->route('admin.cadastros.departments.index')
            ->with('success', 'Cor do departamento atualizada com sucesso.');
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

    public function update(Request $request, string $user): RedirectResponse
    {
        $targetUser = User::query()->findOrFail($user);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:80',
                Rule::unique('users', 'name')->ignore($targetUser->id, 'id'),
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:180',
                Rule::unique('users', 'email')->ignore($targetUser->id, 'id'),
            ],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
        ]);

        $password = $validated['password'] ?? null;
        if ($password === '') {
            $password = null;
        }

        $normalizedEmail = strtolower($validated['email']);
        $emailChanged = $normalizedEmail !== strtolower((string) $targetUser->email);
        $nameChanged = $validated['username'] !== $targetUser->name;
        $passwordChanged = $password !== null;

        if ($emailChanged || $passwordChanged) {
            try {
                $this->firebaseUserProvisioningService->updateCredentials(
                    (string) $targetUser->id,
                    $validated['username'],
                    $normalizedEmail,
                    $password
                );
            } catch (RuntimeException $exception) {
                $field = $passwordChanged ? 'password' : 'email';

                return back()
                    ->withErrors([$field => $exception->getMessage()])
                    ->withInput();
            } catch (Throwable) {
                return back()
                    ->withErrors(['email' => 'Falha ao atualizar conta no Firebase. Tente novamente.'])
                    ->withInput();
            }
        } elseif ($nameChanged) {
            $this->firebaseUserProvisioningService->tryUpdateDisplayName(
                (string) $targetUser->id,
                $validated['username'],
                $normalizedEmail
            );
        }

        $targetUser->update([
            'name' => $validated['username'],
            'email' => $normalizedEmail,
        ]);

        return redirect()
            ->route('admin.cadastros.users.index')
            ->with('success', 'Usuario atualizado com sucesso.');
    }

    public function destroy(string $user): RedirectResponse
    {
        $targetUser = User::query()->find($user);

        if (! $targetUser instanceof User) {
            return back()->withErrors([
                'delete' => 'Usuario nao encontrado ou ja removido.',
            ]);
        }

        $rootAdminUid = trim((string) env('ADMIN_FIREBASE_UID', ''));
        if ($rootAdminUid !== '' && (string) $targetUser->id === $rootAdminUid) {
            return back()->withErrors([
                'delete' => 'Admin root nao pode ser removido.',
            ]);
        }

        $this->firebaseUserProvisioningService->disableUser((string) $targetUser->id);

        $targetUser->delete();

        return redirect()
            ->route('admin.cadastros.users.index')
            ->with('success', 'Usuario excluido com sucesso.');
    }

    public function syncDepartments(Request $request, string $user): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'string', Rule::exists('departments', 'id')],
        ]);

        $targetUser = User::query()->findOrFail($user);

        $departmentSlug = DB::table('departments')
            ->where('id', $validated['department_id'])
            ->value('slug');

        $role = $this->resolveRoleByDepartmentSlug(
            is_string($departmentSlug) ? $departmentSlug : ''
        );

        $targetUser->update([
            'department_id' => $validated['department_id'],
            'role' => $role,
        ]);

        return redirect()
            ->route('admin.cadastros.users.index')
            ->with('success', 'Departamento atualizado com sucesso.');
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
                'users.department_id',
                'departments.name as department_name',
                'departments.slug as department_slug',
            ])
            ->map(function (User $user) use ($rootAdminUid): array {
                $departmentSlug = (string) ($user->department_slug ?? '');
                $departmentLabel = $this->formatDepartmentLabel(
                    $departmentSlug,
                    (string) ($user->department_name ?? '')
                );

                return [
                    'id' => (string) $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'department_id' => (string) ($user->department_id ?? ''),
                    'department_slug' => $departmentSlug,
                    'departments' => [$departmentLabel],
                    'is_root_admin' => $rootAdminUid !== '' && (string) $user->id === $rootAdminUid,
                ];
            })
            ->values()
            ->all();
    }

    private function getDishCategories(): array
    {
        return DB::table('dish_categories')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(function (object $category): array {
                $activeCount = DB::table('dishes')
                    ->where('category_id', $category->id)
                    ->where('active', true)
                    ->count();

                return [
                    'id' => (string) $category->id,
                    'name' => (string) $category->name,
                    'slug' => (string) $category->slug,
                    'dishes_count' => $activeCount,
                ];
            })
            ->values()
            ->all();
    }

    private function getDishes(): array
    {
        return DB::table('dishes')
            ->join('dish_categories', 'dishes.category_id', '=', 'dish_categories.id')
            ->orderBy('dishes.name')
            ->get([
                'dishes.id',
                'dishes.name',
                'dishes.description',
                'dishes.price',
                'dishes.photo_path',
                'dishes.category_id',
                'dishes.active',
                'dish_categories.name as category_name',
            ])
            ->map(function (object $dish): array {
                $photoUrl = $dish->photo_path
                    ? '/storage/'.ltrim(str_replace('\\', '/', (string) $dish->photo_path), '/')
                    : null;

                return [
                    'id' => (string) $dish->id,
                    'name' => (string) $dish->name,
                    'description' => $dish->description !== null ? (string) $dish->description : '',
                    'price' => (float) $dish->price,
                    'photo_path' => $dish->photo_path !== null ? (string) $dish->photo_path : null,
                    'photo_url' => $photoUrl,
                    'category_id' => (string) $dish->category_id,
                    'category_name' => (string) $dish->category_name,
                    'active' => (bool) $dish->active,
                ];
            })
            ->values()
            ->all();
    }

    private function getDepartments(): array
    {
        return DB::table('departments')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color'])
            ->map(fn (object $department): array => [
                'id' => (string) $department->id,
                'name' => (string) $department->name,
                'slug' => (string) $department->slug,
                'label' => $this->formatDepartmentLabel(
                    (string) $department->slug,
                    (string) $department->name
                ),
                'color' => $department->color
                    ? strtoupper((string) $department->color)
                    : $this->defaultColorForSlug((string) $department->slug),
            ])
            ->values()
            ->all();
    }

    private function defaultColorForSlug(string $slug): string
    {
        return match (strtolower(trim($slug))) {
            'admin' => '#993C1D',
            'kitchen' => '#E67E22',
            'finance' => '#2B6CB0',
            'waiter' => '#38A169',
            default => '#5E6B7A',
        };
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

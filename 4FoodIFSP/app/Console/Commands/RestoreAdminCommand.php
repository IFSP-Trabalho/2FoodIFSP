<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RestoreAdminCommand extends Command
{
    protected $signature = 'admin:restore';

    protected $description = 'Recria o registro do admin no banco de dados e garante a conta no Firebase Auth.';

    public function handle(): int
    {
        $adminEmail = 'admin@restaurante.com';
        $adminPassword = 'admin123';
        $apiKey = config('services.firebase.web_api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            $this->error('FIREBASE_WEB_API_KEY não configurada no .env.');

            return self::FAILURE;
        }

        $uid = $this->provisionFirebase($adminEmail, $adminPassword, $apiKey);

        if ($uid === null) {
            $this->error('Não foi possível obter o UID do Firebase. Verifique as credenciais e a chave de API.');

            return self::FAILURE;
        }

        $this->persistInDatabase($uid, $adminEmail);
        $this->updateEnvAdminUid($uid);

        $this->info("Admin restaurado com sucesso!");
        $this->line("  E-mail : {$adminEmail}");
        $this->line("  UID    : {$uid}");

        return self::SUCCESS;
    }

    private function provisionFirebase(string $email, string $password, string $apiKey): ?string
    {
        $response = Http::post(
            "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$apiKey}",
            [
                'email' => $email,
                'password' => $password,
                'displayName' => 'Administrador',
                'returnSecureToken' => true,
            ]
        );

        if ($response->successful()) {
            $uid = $response->json('localId');
            $this->line("Conta Firebase criada com novo UID: {$uid}");

            return is_string($uid) ? $uid : null;
        }

        $errorCode = $response->json('error.message');

        if ($errorCode === 'EMAIL_EXISTS') {
            $this->line('Conta Firebase já existe. Usando UID do .env.');

            $uid = trim((string) env('ADMIN_FIREBASE_UID', ''));

            if ($uid === '') {
                $this->error('ADMIN_FIREBASE_UID não está definido no .env e a conta já existe no Firebase.');

                return null;
            }

            return $uid;
        }

        $this->error("Erro ao acessar o Firebase: {$errorCode}");

        return null;
    }

    private function persistInDatabase(string $uid, string $email): void
    {
        $adminDepartmentId = DB::table('departments')
            ->where('slug', 'admin')
            ->value('id');

        if ($adminDepartmentId === null) {
            $this->error('Departamento admin não encontrado no banco. Execute as migrations/seeders primeiro.');

            return;
        }

        // Admin recriado no Firebase ganha novo UID; remove linha antiga com o mesmo e-mail.
        DB::table('users')
            ->where('email', $email)
            ->where('id', '!=', $uid)
            ->delete();

        DB::table('users')->updateOrInsert(
            ['id' => $uid],
            [
                'name' => 'Administrador',
                'email' => $email,
                'role' => 'admin',
                'department_id' => $adminDepartmentId,
                'must_reset_password' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->line('Registro inserido/atualizado no banco.');
    }

    private function updateEnvAdminUid(string $uid): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'ADMIN_FIREBASE_UID=')) {
            $envContent = preg_replace('/^ADMIN_FIREBASE_UID=.*/m', "ADMIN_FIREBASE_UID={$uid}", $envContent);
        } else {
            $envContent .= "\nADMIN_FIREBASE_UID={$uid}";
        }

        file_put_contents($envPath, $envContent);
        $this->line('.env atualizado com ADMIN_FIREBASE_UID.');
    }
}

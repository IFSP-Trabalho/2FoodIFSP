# Fluxo: Edição de usuário (Admin > Cadastros > Usuários)

> **Tipo:** spec de implementação para IA  
> **Escopo:** editar dados de contato/login do usuário (nome, e-mail, senha). **Não** altera departamento — isso permanece em `gestaoDepartamentosUsuario.md`.  
> **Depende de:** `docs/flow/cadastroUsuario.md`, `docs/features/cadastros.md`, `docs/database/schema.md`, `docs/features/auth.md`  
> **Rota já existente (stub):** `PUT /admin/cadastros/users/{user}` → `UsersController@update`

---

## Objetivo

Permitir que o admin edite um usuário já cadastrado a partir do ícone **Editar** (lápis) na listagem. A interface reutiliza o **mesmo painel/modal** do cadastro (`UserCreatePanel` / classes `admin-modal`), mas **somente** com os campos:

| Campo na UI | Campo persistido | Obrigatório na edição |
|-------------|------------------|------------------------|
| Nome usuário | `users.name` + `displayName` no Firebase | Sim |
| E-mail | `users.email` + `email` no Firebase | Sim |
| Senha | Firebase Auth `password` | Não (ver regra abaixo) |

Após salvar, o usuário deve conseguir **fazer login normalmente** com o e-mail e a senha vigentes (Firebase e registro local alinhados).

---

## Gatilho

1. Admin acessa `/admin/cadastros/users`.
2. Na linha do usuário, clica no botão **Editar** (`UsersTableRow` emite `edit`).
3. Abre o **mesmo menu modal** usado em “Adicionar”, porém em modo edição (título e campos diferentes).

Hoje existe stub em `Users.vue` (`handleEdit` com `window.alert`). **Substituir** por este fluxo.

---

## UI — painel de edição (mesmo shell do cadastro)

### Reutilização visual obrigatória

- Mesmas classes e layout de `UserCreatePanel.vue` + `resources/js/Components/styles/AdminModal.css`.
- Overlay igual ao cadastrar usuário, modal centralizado, floating labels, toggle mostrar/ocultar senha, botões **Sair** e **Salvar**.
- **Não** exibir o campo `departamento` / `DepartmentSelect` na edição.
- CSS continua **externo** (`AdminModal.css`); não embutir estilos no `.vue`.

### Conteúdo do painel (modo edição)

| Elemento | Valor |
|----------|--------|
| Título | `Editar usuario` |
| Subtítulo opcional | Nome atual: `{{ user.name }}` (texto secundário no header) |
| Campo 1 | Nome usuario — pré-preenchido com `user.name` |
| Campo 2 | E-mail — pré-preenchido com `user.email` |
| Campo 3 | Senha — **vazio** ao abrir; placeholder ou hint: `Deixe em branco para manter a senha atual` |
| Botão Sair | Fecha modal, reseta form e limpa erros |
| Botão Salvar | Envia `PUT` (desabilitado durante `processing`) |

### Comportamento do formulário

```js
// estado local ao abrir edição
const form = useForm({
  username: user.name,
  email: user.email,
  password: '', // sempre vazio na abertura
});

// dirty check: habilitar Salvar só se algo mudou
const hasChanges = computed(() =>
  form.username !== user.name ||
  form.email !== user.email ||
  form.password.trim() !== ''
);
```

- **Salvar** desabilitado se `!hasChanges` ou `form.processing`.
- Fechar com overlay, `Esc` ou **Sair** descarta alterações (mesmo padrão do create).
- Erros de validação Inertia exibidos abaixo de cada campo (`form.errors.*`).

### Componentização sugerida

| Abordagem | Descrição |
|-----------|-----------|
| **Preferida** | Criar `UserEditPanel.vue` espelhando `UserCreatePanel.vue`, importando o mesmo CSS, sem `DepartmentSelect`. |
| Alternativa | Um único `UserFormPanel.vue` com prop `mode: 'create' \| 'edit'` — só se reduzir duplicação sem aumentar complexidade. |

---

## Regras de negócio

### O que pode ser editado

- Apenas **nome**, **e-mail** e **senha** (Firebase + `users`).
- **Departamento** não entra neste fluxo (continua pelo painel “Gerir departamentos”).

### Senha na edição

| Situação | Comportamento |
|----------|----------------|
| Campo senha **vazio** | Não alterar senha no Firebase; atualizar só nome/e-mail se mudaram. |
| Campo senha **preenchido** | Validar `min:6`, `max:80`; atualizar `password` no Firebase Auth. |
| Após troca de senha pelo admin | **Não** alterar `must_reset_password` — o admin definiu a senha que o usuário usará no próximo login. |

### Unicidade

- `username` (mapeado para `users.name`): único na tabela `users`, **ignorando** o próprio usuário.
- `email`: único em `users.email`, **ignorando** o próprio usuário; normalizar com `strtolower()` antes de salvar (igual ao `store`).

### Usuários especiais

| Caso | Regra |
|------|-------|
| Admin root (`is_root_admin`) | **Permitir** editar nome, e-mail e senha (mesmo fluxo). |
| Usuário soft-deleted | Não aparece na listagem; `update` retorna 404 se tentar via API direta. |
| Conta desabilitada no Firebase | Fora do escopo desta entrega (exclusão já desabilita). |

### Login após edição (critério central)

O login usa Firebase (e-mail + senha no client) e depois valida `User::find($uid)` em `AuthController@firebaseLogin`.

Para o login continuar funcionando após a edição:

1. **`users.id` (Firebase UID) não muda** — nunca recriar usuário no Firebase na edição.
2. Se o **e-mail** mudou: atualizar `email` no Firebase **e** em `users.email` na mesma operação lógica.
3. Se a **senha** foi informada: atualizar `password` no Firebase Auth.
4. Se o **nome** mudou: atualizar `users.name` e `displayName` no Firebase.
5. Transação recomendada: **Firebase primeiro**; se Firebase falhar, não persistir no banco. Se Firebase OK e banco falhar, retornar erro claro (estado inconsistente é raro; logar para suporte).

**Teste manual de aceite:** após salvar, fazer logout e login com o usuário editado usando o **novo e-mail** (se alterado) e a **senha vigente** (nova ou a anterior se senha ficou em branco).

---

## Integração backend

### Request

```
PUT /admin/cadastros/users/{user}
Content-Type: application/json (Inertia)

{
  "username": "João Silva",
  "email": "joao@exemplo.com",
  "password": ""          // opcional; omitir ou string vazia = não alterar senha
}
```

> O frontend pode enviar `password` apenas quando preenchido; o backend deve tratar `null`, ausente e `""` como “não alterar”.

### Validação (`UsersController@update`)

```php
$targetUser = User::query()->findOrFail($user);

$validated = $request->validate([
    'username' => [
        'required', 'string', 'min:3', 'max:80',
        Rule::unique('users', 'name')->ignore($targetUser->id, 'id'),
    ],
    'email' => [
        'required', 'email:rfc,dns', 'max:180',
        Rule::unique('users', 'email')->ignore($targetUser->id, 'id'),
    ],
    'password' => ['nullable', 'string', 'min:6', 'max:80'],
]);
```

### Serviço Firebase — novo método

Estender `FirebaseUserProvisioningService` (ou criar `FirebaseUserUpdateService`) com algo como:

```php
public function updateCredentials(
    string $uid,
    string $displayName,
    string $email,
    ?string $password = null
): void
```

Implementação via Admin SDK (mesmo padrão de `disableUser`):

```php
$properties = [
    'displayName' => $displayName,
    'email' => $email,
];
if ($password !== null && $password !== '') {
    $properties['password'] = $password;
}
Firebase::auth()->updateUser($uid, $properties);
```

- Se Admin SDK indisponível: retornar erro amigável (`Falha ao atualizar conta no Firebase. Tente novamente.`) — **não** atualizar só o banco.
- Tratar erros comuns do Firebase (e-mail já em uso em outra conta, senha fraca) e mapear para `withErrors` quando possível.

### Persistência local

Após sucesso no Firebase:

```php
$targetUser->update([
    'name' => $validated['username'],
    'email' => strtolower($validated['email']),
    // role e department_id: intocados
    // must_reset_password: intocado
]);
```

### Response

```php
return redirect()
    ->route('admin.cadastros.users.index')
    ->with('success', 'Usuario atualizado com sucesso.');
```

### Remover stub

Substituir o corpo atual de `update()` que apenas faz `return back();`.

---

## Integração frontend (Inertia)

### `Users.vue`

```js
const editPanelUser = ref(null);

function handleEdit(user) {
  editPanelUser.value = user;
}

function closeEditPanel() {
  editPanelUser.value = null;
}
```

Template:

```vue
<UserEditPanel
  v-if="editPanelUser"
  :user="editPanelUser"
  @close="closeEditPanel"
/>
```

### `UserEditPanel.vue` — submit

```js
form.put(`/admin/cadastros/users/${props.user.id}`, {
  preserveScroll: true,
  onSuccess: () => emit('close'),
});
```

Payload enviado: sempre `username` e `email`; incluir `password` somente se `form.password.trim() !== ''` (ou enviar vazio e deixar backend ignorar).

---

## Arquivos a criar / alterar

| Arquivo | Ação |
|---------|------|
| `resources/js/Components/UserEditPanel.vue` | **Criar** — modal edição (nome, e-mail, senha) |
| `resources/js/Pages/Admin/Cadastros/Users.vue` | **Alterar** — estado `editPanelUser`, remover `window.alert` |
| `app/Http/Controllers/Admin/UsersController.php` | **Alterar** — implementar `update()` |
| `app/Services/FirebaseUserProvisioningService.php` | **Alterar** — adicionar `updateCredentials()` |
| `resources/js/Components/UserCreatePanel.vue` | **Sem mudança obrigatória** |
| `resources/js/Components/UsersTableRow.vue` | **Manter** emit `edit` |
| `docs/features/cadastros.md` | **Atualizar** checklist — marcar `update` como implementado após entrega |

---

## Acessibilidade e UX

- `role="dialog"`, `aria-modal="true"`, `aria-labelledby` apontando para o título do painel.
- Botão lápis: `aria-label="Editar usuario de {nome}"`.
- Loading: texto do botão Salvar `Salvando...` e `:disabled="form.processing"`.
- Flash de sucesso na listagem após redirect (já existe `flashSuccess` em `Users.vue`).

---

## O que NÃO fazer nesta entrega

- Alterar departamento ou `role` neste painel (usar `PUT .../departments`).
- Recriar usuário no Firebase (novo UID).
- CRUD de departamentos ou pratos.
- Paginação, busca server-side ou upload de avatar.
- Forçar `must_reset_password = true` após edição pelo admin (a menos que produto peça depois).

---

## Casos de erro

| Erro | Comportamento esperado |
|------|------------------------|
| 422 validação | Modal permanece aberto; erros nos campos |
| E-mail duplicado (outro usuário) | `form.errors.email` |
| Nome duplicado | `form.errors.username` |
| Firebase falha | `form.errors.email` ou chave genérica `firebase` |
| Usuário inexistente | 404 (Inertia trata conforme app) |

---

## Critérios de aceite (checklist para IA)

- [ ] Clicar em **Editar** abre modal com o mesmo visual do cadastro (`admin-modal`).
- [ ] Modal exibe apenas **Nome usuario**, **E-mail** e **Senha** (sem departamento).
- [ ] Nome e e-mail vêm pré-preenchidos; senha inicia vazia com hint de “manter atual”.
- [ ] **Salvar** desabilitado se nenhum campo foi alterado.
- [ ] **Salvar** chama `PUT /admin/cadastros/users/{id}` com payload válido.
- [ ] Backend atualiza Firebase (`displayName`, `email`, `password` se informada) e tabela `users`.
- [ ] `users.id` permanece o mesmo após edição.
- [ ] Listagem recarrega; nome/e-mail na tabela refletem os novos valores.
- [ ] **Login manual:** usuário edita e consegue autenticar com e-mail/senha corretos.
- [ ] Senha em branco: login continua com senha anterior.
- [ ] Senha preenchida: login funciona com a nova senha.
- [ ] E-mail alterado: login funciona com o novo e-mail.
- [ ] Stub `window.alert` em `handleEdit` removido.
- [ ] CSS permanece em `AdminModal.css` (sem CSS inline no `.vue`).

---

## Referência visual (wireframe ASCII)

Mesmo container do cadastro, sem linha de departamento:

```
┌─────────────────────────────────────┐
│ Editar usuario                      │
│ Maria Santos                        │
├─────────────────────────────────────┤
│ [ Nome usuario    Maria Santos   ]  │
│ [ E-mail          maria@...      ]  │
│ [ Senha           (vazio)        ]👁 │
│   Deixe em branco para manter...    │
├─────────────────────────────────────┤
│              [ Sair ] [ Salvar ]    │
└─────────────────────────────────────┘
```

---

## Fluxo resumido (sequência)

```mermaid
sequenceDiagram
    participant Admin
    participant UI as UserEditPanel
    participant API as UsersController
    participant FB as Firebase Auth
    participant DB as users

    Admin->>UI: Clica Editar
    UI->>UI: Preenche nome/email; senha opcional
    Admin->>UI: Salvar
    UI->>API: PUT /users/{uid}
    API->>API: Valida unicidade
    API->>FB: updateUser(uid, displayName, email, password?)
    FB-->>API: OK
    API->>DB: UPDATE name, email
    API-->>UI: Redirect + flash success
    Admin->>FB: Login (email + senha)
    FB-->>Admin: idToken
    Admin->>API: firebaseLogin
    API->>DB: User::find(uid)
    API-->>Admin: Sessão OK
```

---

## Próximo passo

Com esta spec aprovada, implementar na ordem:

1. `FirebaseUserProvisioningService::updateCredentials`
2. `UsersController@update`
3. `UserEditPanel.vue` + integração em `Users.vue`
4. Testes manuais de login (cenários: só nome, só email, senha nova, tudo junto)

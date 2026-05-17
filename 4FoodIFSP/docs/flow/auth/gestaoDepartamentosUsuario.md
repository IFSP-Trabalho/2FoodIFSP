# Fluxo: Gestão de departamento do usuário (Admin > Cadastros > Usuários)

> **Tipo:** spec de implementação para IA  
> **Escopo:** apenas gestão de departamento na **listagem** de usuários (ação por linha). Não altera o fluxo de criação em `cadastroUsuario.md`.  
> **Depende de:** `docs/flow/cadastroUsuario.md`, `docs/features/cadastros.md`, `docs/database/schema.md`  
> **Rota já existente:** `PUT /admin/cadastros/users/{user}/departments` → `UsersController@syncDepartments`

---

## Objetivo

Permitir que o admin altere o departamento de um usuário já cadastrado, a partir do ícone **Gerir departamentos** na coluna de ações da tabela de usuários. A interface usa **checkboxes** em um painel quadrado, mas o comportamento é de **seleção única**: no máximo **1 departamento por usuário**.

---

## Gatilho

1. Admin acessa `/admin/cadastros/users`.
2. Na linha do usuário, clica no botão/ícone **Gerir departamentos** (`UsersTableRow.vue` emite `manageDepartments`).
3. Abre o **menu quadrado** de gestão de departamentos (popover/modal compacto).

Hoje existe stub em `Users.vue` (`handleManageDepartments` com `window.alert`). **Substituir** por este fluxo.

---

## UI — menu quadrado de departamentos

### Formato do painel

- Painel **quadrado** (largura ≈ altura), sobreposto à linha ou ancorado ao botão de ações.
- sombra suave, fundo branco.
- Fechar ao clicar fora, no `Esc`, ou em botão **Cancelar** / **Fechar**.
- CSS **externo** (obrigatório): ex. `resources/js/Components/styles/DepartmentManagePanel.css` — não embutir CSS no `.vue`.

### Conteúdo

- Título curto: `Departamentos`.
- Subtítulo opcional com nome do usuário: `{{ user.name }}`.
- **Listagem vertical** dos 4 departamentos fixos do sistema (ordem fixa):

| Ordem | Label na UI | Slug (`departments.slug`) | Corzinha (indicador) |
|------:|-------------|---------------------------|----------------------|
| 1 | Admin | `admin` | `departments.color` (fallback `#993C1D`) |
| 2 | Kitchen | `kitchen` | `departments.color` (fallback `#E67E22`) |
| 3 | Financeiro | `finance` | `departments.color` (fallback `#2B6CB0`) |
| 4 | Garçom | `waiter` | `departments.color` (fallback `#38A169`) |

> **Corzinha:** pequeno círculo ou barra colorida à esquerda de cada item da lista (4–8px), alinhado ao checkbox e ao texto. Serve só para identificação visual; não é botão. A cor vem de `resolveDepartmentColor()` em `departmentOptions.js` (mesma fonte que Cadastros > Departamentos). Ver `docs/flow/depts/edicaoDepartamento.md`.

### Item da listagem (cada departamento)

```
[corzinha] [checkbox] Label do departamento
```

- Checkbox nativo ou componente acessível (`role="checkbox"`, `aria-checked`).
- Label clicável marca/desmarca o checkbox.
- Lista sempre exibe os **4** itens; dados vêm de `props.departments` (backend), mapeados por `slug`.

### Estado inicial ao abrir

- Marcar o checkbox do departamento **atual** do usuário (`user.department_id` ou equivalente vindo do `index`).
- Se o usuário não tiver departamento (caso excepcional), nenhum checkbox marcado.

---

## Regra de negócio — 1 departamento por usuário (checkbox exclusivo)

**Modelo mental:** checkbox na UI, **radio na regra**.

| Situação | Comportamento |
|----------|----------------|
| Nenhum marcado | Usuário sem departamento selecionado no painel (só até salvar; ver backend). |
| Marcar departamento A | A fica marcada; B, C e D são **desmarcadas automaticamente**. |
| A já marcada; clicar em B | B marca; A desmarca. Não é possível ter A e B marcados ao mesmo tempo. |
| A já marcada; clicar em A (desmarcar) | A desmarca; todos ficam desmarcados. Só então o admin pode marcar outro. |
| Tentar marcar B com A ainda marcada | **Não bloquear:** ao marcar B, desmarcar A na mesma ação (comportamento preferido para UX fluida). |

**Proibido:** dois ou mais checkboxes marcados simultaneamente.

**Implementação sugerida (frontend):**

```js
// estado local: selectedDepartmentId: string | null
function onToggleDepartment(departmentId) {
  if (selectedDepartmentId.value === departmentId) {
    selectedDepartmentId.value = null; // desmarca o atual
    return;
  }
  selectedDepartmentId.value = departmentId; // marca um; implicitamente só um
}
```

Não usar `v-model` em array de múltiplos IDs. Usar **um único** `selectedDepartmentId`.

---

## Ações do painel

| Botão | Comportamento |
|-------|----------------|
| **Salvar** | Envia `PUT` com `department_id` selecionado. Fecha painel e atualiza listagem (Inertia). |
| **Cancelar** / **Fechar** | Descarta alterações locais; restaura seleção inicial; fecha painel. |

- **Salvar** desabilitado se nada mudou em relação ao estado inicial.
- **Salvar** desabilitado se `selectedDepartmentId === null` (backend exige departamento — ver validação).
- Loading no botão Salvar durante a requisição; evitar duplo clique.

---

## Integração backend

### Request

```
PUT /admin/cadastros/users/{user}/departments
Content-Type: application/json (ou form Inertia)

{
  "department_id": "<uuid do departments.id>"
}
```

### Validação (já alinhada ao controller)

- `department_id`: `required`, `string`, `exists:departments,id`

### Efeitos colaterais (já implementados em `syncDepartments`)

1. Atualiza `users.department_id`.
2. Recalcula `users.role` pelo slug do departamento:
   - `admin` → `admin`
   - `kitchen` → `kitchen`
   - `finance` → `finance`
   - `waiter` → `waiter`
3. Redirect para `admin.cadastros.users.index` com flash `Departamento atualizado com sucesso.`

### Frontend (Inertia)

```js
import { router } from '@inertiajs/vue3';

router.put(
  route('admin.cadastros.users.syncDepartments', { user: user.id }),
  { department_id: selectedDepartmentId.value },
  {
    preserveScroll: true,
    onFinish: () => closePanel(),
  }
);
```

Usar helper `route()` se o projeto expõe Ziggy; caso contrário, URL literal `/admin/cadastros/users/${user.id}/departments`.

---

## Arquivos a criar / alterar

| Arquivo | Ação |
|---------|------|
| `resources/js/Components/DepartmentManagePanel.vue` | **Criar** — painel quadrado + listagem + checkboxes exclusivos |
| `resources/js/Components/styles/DepartmentManagePanel.css` | **Criar** — estilos do painel |
| `resources/js/Pages/Admin/Cadastros/Users.vue` | **Alterar** — estado do painel, `handleManageDepartments`, remover `window.alert` |
| `resources/js/Components/UsersTableRow.vue` | **Manter** emit `manageDepartments` (sem mudança obrigatória) |
| `app/Http/Controllers/Admin/UsersController.php` | **Sem mudança** se `syncDepartments` já atende |

Opcional: após salvar, badge na coluna **Departamentos** da linha deve refletir o novo label (`Admin`, `Kitchen`, `Financeiro`, `Garçom`).

---

## Acessibilidade e UX

- Foco preso no painel enquanto aberto (trap opcional).
- `aria-label` no botão da engrenagem: `Gerir departamentos de {nome}`.
- Mensagem de erro do Inertia exibida dentro do painel ou como toast abaixo do título.
- Em mobile: painel centralizado ou full-width com max-width; manter proporção “quadrada” quando couber.

---

## Casos especiais

| Caso | Regra |
|------|-------|
| Admin root (`is_root_admin`) | Permitir abrir painel e trocar departamento **somente** se produto confirmar; por padrão, **permitir** troca de departamento mas **nunca** permitir exclusão do usuário (já existente). |
| Erro de rede / 422 | Manter painel aberto; exibir erros de validação. |
| Lista `departments` vazia no props | Não abrir painel; log/alert interno `Departamentos indisponíveis`. |

---

## O que NÃO fazer nesta entrega

- CRUD da tabela `departments` (tela “Departamentos” do submenu continua `Em breve`).
- Múltiplos departamentos por usuário (N:N) — modelo é **1:1** `users.department_id`.
- Alterar o select de departamento do formulário **Adicionar** (permanece como em `cadastroUsuario.md`).
- Radio buttons visuais — usar **checkbox** com lógica exclusiva descrita acima.

---

## Critérios de aceite (checklist para IA)

- [ ] Clicar em **Gerir departamentos** abre painel quadrado com 4 itens em listagem vertical.
- [ ] Cada item tem **corzinha**, checkbox e label (`Admin`, `Kitchen`, `Financeiro`, `Garçom`).
- [ ] Ao abrir, o departamento atual do usuário aparece marcado.
- [ ] Marcar outro departamento desmarca o anterior automaticamente; nunca há 2+ marcados.
- [ ] Desmarcar o único marcado deixa todos desmarcados; marcar outro só após desmarcar ou via troca direta (marcar B desmarca A).
- [ ] **Salvar** chama `PUT .../departments` com um `department_id` válido.
- [ ] Listagem recarrega e badge de departamento atualiza após sucesso.
- [ ] CSS em arquivo externo na pasta `styles`.
- [ ] Stub `window.alert` em `handleManageDepartments` removido.

---

## Referência visual (wireframe ASCII)

```
┌─────────────────────────┐
│ Departamentos           │
│ João Silva              │
├─────────────────────────┤
│ ● ■ Admin               │
│ ● □ Kitchen             │
│ ● □ Financeiro          │
│ ● □ Garçom              │
├─────────────────────────┤
│      [Cancelar][Salvar] │
└─────────────────────────┘

● = corzinha    ■ = checkbox marcado    □ = desmarcado
```

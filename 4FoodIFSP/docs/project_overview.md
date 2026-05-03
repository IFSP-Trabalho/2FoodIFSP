# Restaurant Management System — Project Overview (Técnico)

> Versão: 2.0 — Atualizado com módulo WhatsApp, Garçom e delivery tracking  
> Stack base: Laravel (PHP), frontend framework a definir (Livewire / Inertia + Vue ou React), MySQL/PostgreSQL

---

## 1. Visão Geral

Sistema de gestão de restaurante multi-departamento com controle de acesso baseado em funções (RBAC). O sistema opera em duas superfícies principais: um **painel de gestão web** (usado por funcionários e admin) e uma **interface de tablet** (usada pelo cliente para registrar pedidos presenciais). O sistema possui ainda um **subdomínio de delivery** onde o cliente pode acompanhar o status do pedido e realizar avaliações.

O produto é concebido como um SaaS, permitindo que restaurantes contratem módulos opcionais — especialmente o módulo de integração com WhatsApp.

---

## 2. Stack Tecnológica


| Camada                    | Tecnologia                                      |
| ------------------------- | ----------------------------------------------- |
| Backend                   | Laravel (PHP 8.x)                               |
| Frontend (painel)         | Vue/React                                       |
| Frontend (tablet/cliente) | Blade                                           |
| Banco de dados            | MySQL                                           |
| Fila de jobs              | Laravel Queue (Redis ou database driver)        |
| Real-time / WebSocket     | Laravel Broadcasting + Pusher ou Laravel Reverb |
| Autenticação              | Laravel Breeze / Sanctum                        |
| Storage de arquivos       | Laravel Storage (local ou S3-compatible)        |
| Integração WhatsApp       | API third-party (Z-API, Twilio)                 |


---

## 2.1 Padrão de estilos frontend (obrigatório)

- Não manter CSS inline dentro de arquivos `.vue` (evitar blocos `<style> ... </style>` com regras escritas no componente).
- Todo estilo deve ficar em arquivo `.css` dedicado por tela/componente, dentro da pasta `styles` do módulo.
- O `.vue` deve apenas referenciar o arquivo externo, por exemplo: `<style scoped src="./styles/NomeDoComponente.css"></style>`.
- Este padrão é obrigatório para todas as novas features e para manutenção de componentes existentes.

---

## 3. Módulos e Domínios do Sistema

### 3.1 Autenticação e RBAC

- Login via **username + password** (sem registro público — apenas admin cria contas)
- Roles: `admin`, `kitchen`, `finance`, `waiter`, `whatsapp_agent`
- Middleware de rota por role
- First-login flow: ao primeiro acesso, o usuário é redirecionado para redefinição obrigatória de senha
- O admin possui credenciais pré-seeded via `DatabaseSeeder`

**Entities:** `users`, `roles`, `departments`, `user_department` (pivot)

---

### 3.2 Módulo de Pedidos (Orders)

Núcleo do sistema. Um pedido (`order`) é criado pelo tablet do cliente ou pelo app de delivery e contém:

- `table_id` — referência à mesa (tablet N = mesa N)
- `origin` — enum: `table` | `delivery`
- `status` — enum: `pending` | `in_progress` | `ready` | `cancelled`
- `paid` — boolean (false por padrão)
- `items[]` — relação com `order_items` (prato, quantidade, observação)

**Fluxo de status:**

```
pending → in_progress → ready
                      → cancelled
```

- Pedidos com `status = in_progress` aparecem na tela "Pedidos em preparo" da cozinha
- Pedidos com `status = ready | cancelled` aparecem no histórico
- Pedidos com `paid = false` compõem o total em aberto de uma mesa

**Broadcast:** Ao criar ou atualizar um pedido, um evento Laravel Broadcasting é disparado para atualizar a cozinha em real-time (sem necessidade de refresh).

---

### 3.3 Módulo Cozinha (Kitchen)

Acesso restrito ao role `kitchen`.

**Telas:**

- **Dashboard:** total de pedidos em preparo, últimos pedidos, tempo médio de preparo
- **Pedidos em preparo:** lista de `orders` com `status = in_progress`. Cada card exibe número da mesa, número do pedido, itens com observações e botão de ação principal
- **Histórico de pedidos:** lista de `orders` com `status = ready | cancelled`, com filtro por intervalo de datas (`date_from`, `date_to`)

**Ações disponíveis pela cozinha:**

- Marcar pedido como `ready`
- Marcar pedido como `cancelled`

---

### 3.4 Módulo Financeiro / Caixa (Finance)

Acesso restrito ao role `finance`.

**Telas:**

- **Dashboard de métricas:** indicadores financeiros do período
- **Gestão de mesas:** visualização de todas as mesas com pedidos em aberto

**Métricas exibidas:**

- Total de pedidos (dia / semana / mês)
- Pedidos em andamento
- Faturamento total (`ganhos`)
- Despesas (campo editável pelo admin)
- Faturamento do dia
- Número de mesas atendidas

**Gestão de mesas:**

- Cards ou lista de mesas com pedidos registrados
- Cada mesa exibe: número da mesa, valor total (`sum` de `order_items.price` onde `paid = false`), status (`OPEN` | `PAID`)
- Ação: `[Ver detalhes]` — lista todos os pedidos da mesa com itens, preços individuais, status de cada pedido e observações
- Ação: `[Fechar conta]` — executa transação que marca todos os pedidos da mesa como `paid = true`

**Regra de negócio:** Apenas pedidos com `paid = false` são computados no total da mesa. Pedidos pagos permanecem no banco para fins de histórico e relatórios.

---

### 3.5 Módulo Garçom (Waiter) — Novo

Acesso restrito ao role `waiter`.

O garçom possui interface própria focada em operação presencial.

**Responsabilidades:**

- Visualizar pedidos ativos por mesa
- Cada pedido exibido com: número da mesa, itens solicitados, observações do cliente
- Realizar fechamento de conta presencialmente (equivalente à ação do financeiro, porém acessível sem o dashboard completo)

**Tela principal:**

- Lista de mesas ativas com pedidos `in_progress` ou `ready` e `paid = false`
- Ao selecionar mesa: exibe detalhamento dos pedidos (itens + observações + total)
- Botão `[Fechar conta]` para marcar mesa como paga

> Nota de implementação: a ação de fechamento pode ser a mesma rota do módulo financeiro com verificação de roles permitidos (`finance`, `waiter`).

---

### 3.6 Módulo Admin

Acesso restrito ao role `admin`. Acesso total ao sistema.

**Cadastros gerenciados:**

- **Usuários:** CRUD completo. Campos: nome, e-mail, senha temporária, departamento. Formulário em slide-over/modal inline (sem navegação para nova página)
- **Pratos:** CRUD completo. Campos: nome, descrição, preço, foto (upload), categoria (`main_course` | `drinks` | `desserts` | customizável). Listagem com filtro por categoria
- **Departamentos:** Pré-seeded via migration/seeder (`admin`, `kitchen`, `finance`, `waiter`). Sem interface de criação/edição/exclusão

**Dashboard admin:**

- Resumo de todos os departamentos: pedidos ativos, total faturado, mesas abertas

---

### 3.7 Módulo Tablet (Interface do Cliente)

Subdomínio separado (ex: `tablet.restaurante.com` ou `mesa.restaurante.com`).

- Cada tablet é configurado com um `table_id` fixo (tablet 1 = mesa 1)
- O cliente navega pelo cardápio, seleciona pratos, adiciona observações e confirma o pedido
- Ao confirmar, a ordem é criada via API com `origin = table` e `status = pending`
- A cozinha recebe o pedido em real-time via WebSocket

---

### 3.8 Módulo Delivery

Pedidos com `origin = delivery` entram no mesmo pipeline de `orders`.

**Diferenças de comportamento:**

- O cliente pode acompanhar o status do pedido pelo subdomínio delivery (polling ou WebSocket)
- Status visível para o cliente: `Em preparo`, `Pronto para retirada / Saiu para entrega`, `Entregue`

---

### 3.9 Módulo WhatsApp (SaaS Opcional)

Módulo contratável por instância de restaurante. Integra com a API oficial do WhatsApp Business (WABA) ou provider terceiro.

**Conceito de Tickets:**
Cada conversa iniciada por um cliente no WhatsApp gera um `ticket` no sistema.

**Status dos tickets:**


| Status        | Descrição                                          |
| ------------- | -------------------------------------------------- |
| `triage`      | Primeira mensagem recebida, aguardando atendimento |
| `in_progress` | Ticket em atendimento por um agente                |
| `closed`      | Atendimento encerrado                              |


**Fluxo:**

1. Cliente envia mensagem no WhatsApp → webhook recebe → cria ticket com `status = triage`
2. Agente (role `whatsapp_agent`) acessa o painel de tickets, assume o ticket → `status = in_progress`
3. Agente e cliente trocam mensagens dentro da plataforma (interface estilo chat)
4. Pedido é registrado e associado ao ticket
5. Agente encerra o atendimento → `status = closed`

**Entities:** `wa_tickets`, `wa_messages`, `wa_ticket_orders` (pivot para associar pedidos ao ticket)

**Considerações de implementação:**

- Webhooks da API do WhatsApp precisam de endpoint público (usar ngrok em dev ou domínio real)
- Rate limiting nos envios de mensagem conforme políticas da Meta
- O módulo é ativado/desativado por configuração de feature flag na instância do restaurante (`features.whatsapp = true/false`)

---

## 4. Modelos de Dados Principais

```
users
  id, name, email, password, role_id, department_id, must_reset_password, timestamps

departments
  id, name, slug

roles
  id, name, slug

orders
  id, table_id, origin (enum: table|delivery), status (enum: pending|in_progress|ready|cancelled),
  paid (bool), wa_ticket_id (nullable), customer_name (nullable), timestamps

order_items
  id, order_id, dish_id, quantity, unit_price, note, timestamps

dishes
  id, name, description, price, photo_path, category_id, active, timestamps

dish_categories
  id, name, slug

tables
  id, number, label

wa_tickets
  id, phone_number, customer_name, status (enum: triage|in_progress|closed),
  agent_id (nullable FK → users), timestamps

wa_messages
  id, wa_ticket_id, direction (inbound|outbound), body, timestamps
```

---

## 5. Eventos e Real-time


| Evento               | Canal                            | Consumidor                                |
| -------------------- | -------------------------------- | ----------------------------------------- |
| `OrderCreated`       | `kitchen` channel                | Cozinha (novo pedido aparece sem refresh) |
| `OrderStatusUpdated` | `kitchen`, `delivery.{order_id}` | Cozinha + cliente acompanhando delivery   |
| `TableClosed`        | `finance` channel                | Financeiro / Garçom                       |
| `WaTicketCreated`    | `whatsapp` channel               | Agente WhatsApp                           |


Implementado via **Laravel Broadcasting** com driver Pusher ou **Laravel Reverb** (self-hosted).

---

## 6. Fluxos de Tela por Departamento

### Login

- Campos: `username` + `password`
- Após autenticação: redireciona para dashboard do departamento do usuário
- First login: intercepta e redireciona para `/password/reset`

### Dashboard (dinâmico por role)

- `admin` → resumo multi-departamento
- `kitchen` → pedidos em preparo, últimos pedidos, tempo médio
- `finance` → faturamento, mesas abertas, métricas
- `waiter` → mesas com pedidos ativos

### Tela de Pedidos (Cozinha)

- Tabs: `[Em preparo]` | `[Histórico]`
- Em preparo: cards com mesa, número do pedido, itens, observações, botão primário de ação
- Histórico: tabela com filtro de data (`date_from` → `date_to`), badge de status

### Cadastros (Admin)

- `/admin/users` — listagem com slide-over de criação/edição
- `/admin/dishes` — listagem com filtro por categoria, slide-over de criação/edição, upload de foto
- `/admin/departments` — listagem read-only

### Mesas / Caixa (Finance e Waiter)

- Grid de cards de mesa: número, total, badge de status
- Tela de detalhe: lista de pedidos com itens, preços, observações, total, botão fechar conta

### WhatsApp (Agente)

- Inbox de tickets com filtro por status (`triage` | `in_progress` | `closed`)
- Tela de ticket: histórico de mensagens (estilo chat), campo de resposta, ação para registrar pedido, botão de encerramento

---

## 7. Ideias Futuras (Backlog)

> Itens não priorizados para o MVP, mas que devem ser considerados na modelagem do banco e arquitetura para evitar refatorações custosas.

- **Formas de pagamento:** registro de método utilizado no fechamento da conta (PIX, cartão, dinheiro) com split entre métodos
- **Avaliação de pratos (LatterBox):** cliente avalia prato após consumo; nota e comentário vinculados ao `order_item`; dashboard de média por prato no admin
- **Múltiplos restaurantes (multi-tenant):** separação de dados por `tenant_id` em todas as tabelas principais; painel de superadmin
- **Relatórios exportáveis:** export de histórico de pedidos e financeiro em PDF/CSV
- **Notificações push no tablet:** alertar cliente quando pedido estiver pronto
- **KDS (Kitchen Display System):** tela de cozinha otimizada para dispositivo touchscreen sem teclado
- **Tempo estimado de preparo por prato:** campo `preparation_time_minutes` em `dishes`; cálculo de ETA por pedido
- **Impressão de comanda:** integração com impressora térmica via QZ Tray ou similar

---

## 8. Considerações de Segurança

- Todas as rotas do painel exigem autenticação (`auth` middleware)
- Rotas por departamento utilizam middleware de role (`role:kitchen`, `role:finance`, etc.)
- Tokens de primeiro login expiram após 24h
- Upload de fotos validado por MIME type e tamanho máximo
- Webhooks do WhatsApp validados por HMAC signature da Meta
- Variáveis sensíveis (API keys, DB credentials) gerenciadas via `.env` e nunca commitadas

---

*Documento gerado para servir como contexto base para desenvolvimento incremental por feature. Cada seção pode ser detalhada individualmente conforme a feature for solicitada.*
# Fluxo: Cadastro de Usuario (Admin > Cadastros > Usuarios)

## Objetivo
Permitir que o admin crie usuarios dentro da plataforma a partir do botao `Adicionar`, vinculando cada usuario a um departamento e garantindo que o novo usuario ja consiga autenticar no sistema.

## Gatilho
1. Admin acessa `/admin/cadastros/users`.
2. Admin clica no botao `Adicionar` (ou `Adicionar primeiro usuario` quando a lista estiver vazia).

## Formulario aberto no menu lateral
Ao clicar em `Adicionar`, abre um menu lateral com os campos obrigatorios:
- `nome usuario` (valor salvo no campo `users.name`)
- `departamento` (select carregado da tabela `departments`)
- `email`
- `senha`

Botoes da acao:
- `Sair` -> fecha o menu, limpa formulario e limpa erros.
- `Salvar` -> envia `POST /admin/cadastros/users`.

## Validacoes no backend
Request validado com:
- `username`: obrigatorio, 3-80 chars, unico em `users.name`
- `department_id`: obrigatorio, deve existir em `departments.id`
- `email`: obrigatorio, formato valido, unico em `users.email`
- `password`: obrigatorio, 6-80 chars

## Persistencia e integracao
Depois da validacao:
1. Backend tenta criar usuario no Firebase Auth (`createUser`).
2. Em fallback, usa Identity Toolkit REST (`accounts:signUp`) para obter o `uid`.
3. Backend persiste em `users` com:
   - `id = uid`
   - `name = username`
   - `email`
   - `department_id`
   - `role` mapeado pelo departamento:
     - Admin -> admin
     - Kitchen -> kitchen
     - Financeiro -> finance
     - Garcom -> waiter
   - `must_reset_password = true`
4. Backend redireciona para listagem com flash de sucesso.

## Resultado esperado
- Novo usuario aparece na lista de usuarios em `cadastros/users`.
- Usuario criado passa a conseguir logar na plataforma (conta existente no Firebase + registro em `users`).

## Regra futura ja preparada (ainda desativada)
Foi deixado um scaffold para limite de criacao por liberacao do admin:
- Servico `UserCreationLimitService` com metodo `canCreateUserByPlan()`.
- Atualmente retorna `true` para nao bloquear criacao nesta fase.
- Quando a regra de licenca for ativada, este ponto passara a validar se o total de usuarios permitidos foi atingido.

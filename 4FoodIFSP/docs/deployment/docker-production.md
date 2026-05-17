# Docker Production Playbook

> Objetivo: padronizar a operação do ambiente com Docker para que qualquer retomada do projeto seja rápida, previsível e sem dependência de memória informal.

---

## 1. Escopo deste playbook

Este guia cobre a operação da stack definida em `docker/docker-compose.yml`, com foco em:

- subir e parar serviços com segurança;
- validar saúde básica do ambiente;
- reduzir risco de configuração manual inconsistente.

---

## 2. Pré-requisitos

- Docker Engine + Docker Compose plugin instalados (`docker compose version`);
- acesso ao diretório raiz do projeto (`4FoodIFSP/4FoodIFSP`);
- variáveis de ambiente definidas para cada ambiente (local/staging/prod).

## 2.1 Política de segredos

- Nunca versionar credenciais reais em `docker-compose.yml`.
- Usar `.env` local (não commitado) e/ou secret manager no ambiente de produção.
- Tratar como sensível: senha de root do MySQL, usuários de aplicação, tokens e chaves de API.

---

## 3. Sequência operacional (boot)

No diretório raiz do projeto:

1. Validar sintaxe do compose:
   - `docker compose -f docker/docker-compose.yml config`
2. Subir serviços:
   - `docker compose -f docker/docker-compose.yml up -d`
3. Verificar estado:
   - `docker compose -f docker/docker-compose.yml ps`
4. Acompanhar logs se necessário:
   - `docker compose -f docker/docker-compose.yml logs -f`

---

## 4. Checklist pós-subida

- [ ] Todos os serviços previstos estão `Up` no `docker compose ps`;
- [ ] Banco responde conexões na porta esperada;
- [ ] Ferramentas auxiliares (ex: phpMyAdmin) respondem em HTTP;
- [ ] Não há crash loop nos logs (`restarting`, `exited` recorrente);
- [ ] Aplicação consegue conectar no banco com as credenciais do ambiente.

---

## 5. Operações frequentes

- Parar e remover stack:
  - `docker compose -f docker/docker-compose.yml down`
- Reiniciar um serviço específico:
  - `docker compose -f docker/docker-compose.yml restart <service>`
- Recriar stack após mudança estrutural:
  - `docker compose -f docker/docker-compose.yml down`
  - `docker compose -f docker/docker-compose.yml up -d --build`

---

## 6. Troubleshooting rápido

### 6.1 Porta em uso

- Sintoma: erro de bind de porta (`port is already allocated`).
- Ação: ajustar mapeamento de porta no compose ou encerrar processo conflitando.

### 6.2 Serviço não inicia (exit imediato)

- Sintoma: container com status `Exited`.
- Ação: inspecionar logs do serviço:
  - `docker compose -f docker/docker-compose.yml logs <service>`

### 6.3 Falha de autenticação no banco

- Sintoma: app ou phpMyAdmin não conecta no MySQL.
- Ação: revisar variáveis de ambiente (`MYSQL_*`, `PMA_HOST`) e credenciais efetivamente carregadas.

---

## 7. Automação recomendada (anti-esquecimento)

Para evitar retrabalho e memória manual, padronizar no projeto:

1. Alvo único de operação (Makefile ou scripts npm), por exemplo:
   - `make infra-up`, `make infra-down`, `make infra-logs`;
2. Check de sintaxe (`docker compose config`) antes de deploy;
3. Checklist pós-subida incorporado no processo de release;
4. Atualização obrigatória deste playbook sempre que o compose mudar.

---

## 8. Referências

- Contexto técnico geral: [`docs/project_overview.md`](../project_overview.md)
- Arquivo de infraestrutura: [`docker/docker-compose.yml`](../../docker/docker-compose.yml)

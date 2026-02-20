# Microsserviço de Atores — Oficina SOAT

Microsserviço responsável pela gestão de **clientes**, **veículos** e **usuários** da oficina mecânica SOAT. É um dos 4 microsserviços independentes que compõem o sistema — trabalho de pós-graduação em Arquitetura de Software pela FIAP (Fase 4).

---

## Alunos

| Aluno | RM | Discord | LinkedIn |
|---|---|---|---|
| Felipe | 365154 | felipeoli7eira | [@felipeoli7eira](https://www.linkedin.com/in/felipeoli7eira) |
| Nicolas | 365746 | nic_hcm | [@Nicolas Martins](https://www.linkedin.com/in/nicolas-hcm) |
| William | 365973 | wllsistemas | [@William Francisco Leite](https://www.linkedin.com/in/williamfranciscoleite) |

---

## Material

- **Vídeo de apresentação**: `[placeholder]`
- **Collection Postman / Swagger**: `[placeholder]`
- **Outros microsserviços do sistema**:
  - soat-pedidos-ms: `[placeholder]`
  - soat-pagamentos-ms: `[placeholder]`
  - soat-producao-ms: `[placeholder]`

---

## Sobre o Projeto

O sistema SOAT evoluiu de uma operação local para uma rede de oficinas mecânicas com escopo nacional e múltiplas filiais. Para suportar essa escala com resiliência e tolerância a falhas, o backend foi reestruturado em microsserviços independentes.

Este serviço — **soat-atores-ms** — é o ponto central de cadastro e consulta de atores do sistema:

- **Clientes**: pessoas físicas ou jurídicas que trazem veículos para a oficina
- **Veículos**: automóveis associados a clientes
- **Usuários**: funcionários da oficina (atendentes, mecânicos, comercial, gestores)

---

## Stack e Justificativas

### PHP 8.4 + Laravel 12
Framework maduro, com mais de 10 anos de ecossistema ativo e adoção massiva na indústria. Oferece injeção de dependência nativa (Service Container), ideal para implementar Clean Architecture sem acoplamento entre camadas. Facilita a criação de APIs RESTful com validação, roteamento e camada de autenticação prontos para uso.

### Nginx
Servidor HTTP com arquitetura assíncrona orientada a eventos — os worker processes são não-bloqueantes, lidando com múltiplas conexões simultâneas sem criar um processo por requisição. Isso resulta em alta eficiência de concorrência e menor consumo de memória em comparação com Apache MPM prefork. Utilizado em produção por Netflix, Airbnb e Dropbox.

### PostgreSQL 17.5
Escolhido como banco de dados único do serviço, seguindo o princípio descrito no artigo *"It's 2026, Just Use Postgres"*:

- Uma única estratégia de backup, monitoramento e segurança
- Adotado por 48.000+ empresas em produção: Netflix, Spotify, Uber, Reddit, Discord
- Extensions maduras para casos avançados: PostGIS, JSONB, TimescaleDB, pgvector
- Latência e custo operacional comparáveis ou menores que soluções especializadas
- Menos pontos de falha — três sistemas com 99,9% de uptime combinam para ~99,7% (≈ 26h/ano de indisponibilidade)
- Licença open-source (PostgreSQL License), sem vendor lock-in

### Docker Compose
Orquestração local dos 3 serviços (nginx, php-fpm, postgres) com build automatizado, migrations e configuração de rede interna. Reproduz o ambiente de produção de forma consistente em qualquer máquina.

---

## Arquitetura — Clean Architecture

```
Route → Api (Http layer) → Controller → UseCase → Gateway → Repository → Model/DB
```

### Camadas

| Camada | Localização | Responsabilidade |
|---|---|---|
| **Domain/Entity** | `app/Domain/Entity/{Entidade}/` | Objeto de domínio com validações, interface de repositório, mapper Model↔Entidade |
| **Domain/UseCase** | `app/Domain/UseCase/{Entidade}/` | Lógica de negócio (Create, Read, ReadOne, Update, Delete) |
| **Infrastructure/Gateway** | `app/Infrastructure/Gateway/` | Adaptador entre UseCase e Repository |
| **Infrastructure/Repositories** | `app/Infrastructure/Repositories/` | Implementações Eloquent (PostgreSQL via PDO) |
| **Infrastructure/Controller** | `app/Infrastructure/Controller/` | Orquestra use cases, recebe repositório via `useRepositorio()` |
| **Http/** | `app/Http/` | Classes Api — recebem HTTP request, validam input com `Validator`, chamam controllers |
| **Infrastructure/Presenter** | `app/Infrastructure/Presenter/` | Formata resposta JSON `{ "err": bool, "dados": {...} }` |

### Convenções de domínio

- **UUID** como identificador público (gerado na criação pelo repositório); `id` numérico é interno
- **Timestamps customizados**: `criado_em`, `atualizado_em`, `deletado_em` (sem `created_at`/`updated_at` do Laravel)
- **Soft delete manual**: campo `deletado_em` nullable, sem uso da trait `SoftDeletes`
- **Exceções de domínio**: `DomainHttpException` com código HTTP embutido
- **Formato de erro**: `{ "err": true, "msg": "..." }`

---

## Entidades de Domínio

| Entidade | Atributos |
|---|---|
| **Cliente** | uuid, nome, documento (CPF/CNPJ), email, fone |
| **Veiculo** | uuid, marca, modelo, placa, ano + FK `cliente_id` |
| **Usuario** | uuid, nome, email, senha, perfil (enum), ativo |

**Perfis de usuário**: `atendente` · `comercial` · `mecanico` · `gestor_estoque`

---

## Endpoints da API

Prefixo base: `/api/`

Autenticação: **Bearer JWT** no header `Authorization` (middleware `JsonWebTokenMiddleware`).

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/ping` | Health check (sem autenticação) |
| `POST` | `/api/cliente` | Criar cliente |
| `GET` | `/api/cliente` | Listar clientes |
| `GET` | `/api/cliente/{uuid}` | Detalhar cliente |
| `PUT` | `/api/cliente/{uuid}` | Atualizar cliente |
| `DELETE` | `/api/cliente/{uuid}` | Remover cliente (soft delete) |
| `POST` | `/api/veiculo` | Criar veículo |
| `GET` | `/api/veiculo` | Listar veículos |
| `GET` | `/api/veiculo/{uuid}` | Detalhar veículo |
| `PUT` | `/api/veiculo/{uuid}` | Atualizar veículo |
| `DELETE` | `/api/veiculo/{uuid}` | Remover veículo (soft delete) |
| `POST` | `/api/usuario` | Criar usuário |
| `GET` | `/api/usuario` | Listar usuários |
| `PUT` | `/api/usuario/{uuid}` | Atualizar usuário |
| `DELETE` | `/api/usuario/{uuid}` | Remover usuário (soft delete) |

---

## Setup Local

**Pré-requisitos**: Docker + Docker Compose

```bash
# 1. Clonar o repositório
git clone <url>
cd soat-atores-ms

# 2. Subir os containers (build + migrations automáticas via setup.sh)
docker compose up -d --build
```

O script `containers/php/setup.sh` executa automaticamente `composer install` e `php artisan migrate` na inicialização do container.

### Portas

| Serviço | Porta host | Porta container |
|---|---|---|
| Nginx (API) | **8080** | 80 |
| PostgreSQL | **5433** | 5432 |
| PHP-FPM | interno | 9000 |

### Health check

```
GET http://localhost:8080/api/ping
→ { "err": false, "msg": "pong" }
```

---

## Testes

> **Pré-requisito**: o `setup.sh` do container instala dependências com `--no-dev` por padrão.
> Antes de rodar os testes pela primeira vez (ou após rebuild), instale as dependências de desenvolvimento:

```bash
docker compose exec microservice composer install
```

Após isso, use normalmente:

```bash
# Rodar todos os testes
docker compose exec microservice php artisan test

# Teste específico
docker compose exec microservice php artisan test --filter=NomeDoTeste

# Limpa config de cache + roda testes
docker compose exec microservice composer test

# Com relatório de cobertura (requer xdebug)
docker compose exec microservice php artisan test --coverage
```

### Estrutura (315 testes)

| Diretório | Escopo |
|---|---|
| `tests/Unit/` | Domain, Infrastructure, Http, Exception |
| `tests/Feature/` | Endpoints HTTP completos (integração) |

Relatório HTML gerado em `application/var/coverage/`.
Relatório texto gerado em `application/var/coverage.txt`.

**Cobertura mínima exigida**: 80%

**Evidências de cobertura**: `[placeholder — print ou link do relatório HTML]`

---

## CI/CD

`[placeholder — descrever pipeline após configurar GitHub Actions]`

Pipeline planejado: build dos containers → testes automatizados → análise de qualidade (SonarQube ou similar) → deploy em Kubernetes.

---

## Saga Pattern

### Estratégia: Coreografado (Choreography-based Saga)

Não há orquestrador central. Cada microsserviço reage de forma autônoma a eventos publicados em filas de mensagens. A consistência eventual é garantida pelo encadeamento de eventos entre os serviços.

---

### Os 4 Microsserviços

| # | Serviço | Responsabilidade |
|---|---|---|
| 1 | **atores-ms** | Usuários, clientes, veículos; autenticação JWT |
| 2 | **estoque-servicos-ms** | Estoque de materiais e serviços da oficina |
| 3 | **ordem-ms** | Ordens de serviço, orçamentos, nota fiscal PDF |
| 4 | **pag-ms** | Pagamento de ordens |

Cada serviço possui seu próprio banco de dados (database-per-service). A comunicação síncrona entre MSs é feita via REST com o header `x-soat-ms-app-key`.

---

### Filas Mapeadas

- `ORDEM_CRIACAO`
- `ORDEM_MUDANCA_STATUS`
- `ESTOQUE_ORDEM_RESERVA`
- `ESTOQUE_ORDEM_DEBITAR_RESERVA`
- `ESTOQUE_ORDEM_CANCELAR_RESERVA`
- `ORDEM_PAGAMENTO_PENDENTE`
- `ORDEM_PAGAMENTO_CONFIRMADO`
- `ORDEM_PAGAMENTO_RECUSADO`

---

### Fluxo 1 — Criação de Ordem de Serviço

1. Atendente cria OS → **ordem-ms** grava no DB-ordem
2. **ordem-ms** publica em `[queue]: ordem_criada`
3. **estoque-ms** consome `ordem_criada` → verifica e reserva materiais no DB-estoque
4. **estoque-ms** publica em `[queue]: estoque_verificado`
5. **ordem-ms** consome `estoque_verificado`:
   - Produtos disponíveis → status muda para `AGUARDANDO_APROVACAO`
   - Sem estoque → status muda para `CANCELADA`

> **Obs.:** Não há transação compensatória nesta etapa pois o débito efetivo do estoque ocorre apenas após a aprovação do orçamento.

---

### Fluxo 2 — Aprovação de Orçamento

O atendente aprova ou reprova o orçamento via HTTP:
- `GET /api/ordem/{uuid}/aprovada`
- `GET /api/ordem/{uuid}/reprovada`

**Se aprovado:**
- **ordem-ms** publica em `fila: orcamento_aprovado`
- **pagamento-ms** consome → salva dados → DB-pagamento

**Se reprovado (transação compensatória):**
- **ordem-ms** publica em `fila: orcamento_reprovado`
- **estoque-ms** escuta → devolve produtos ao estoque → DB-estoque

---

### Fluxo 3 — Processamento do Pagamento

- **pagamento-ms** consome `fila: orcamento_aprovado` → tenta gerar link/documento de pagamento
- **Sucesso:** publica em `fila: aguardando_pagamento`
  - **ordem-ms** consome → atualiza status para `AGUARDANDO_PAGAMENTO`
- **Falha:** publica em `fila: erro_pagamento`
  - **ordem-ms** consome → atualiza status para `ERRO_PAGAMENTO`

---

### Fluxo 4 — Confirmação do Pagamento (Webhook)

1. Gateway de pagamento aciona webhook no **pagamento-ms**
2. **pagamento-ms** atualiza DB-pagamento → publica em `fila: pagamento_confirmado`
3. **ordem-ms** escuta `fila: pagamento_confirmado` → atualiza OS → DB-ordem

---

### Papel do atores-ms na Saga

O **atores-ms** é um serviço de suporte, não um participante direto dos fluxos de saga. Ele **não publica nem consome** eventos das filas de ordens.

Suas responsabilidades transversais são:

- **Autenticação**: emite e valida tokens JWT utilizados pelos demais microsserviços
- **Consulta de atores**: os outros MSs consultam este serviço via REST (com `x-soat-ms-app-key`) para verificar dados de clientes, veículos e usuários
- **Fundação**: é pré-requisito para o funcionamento de todo o sistema — sem ele, os outros MSs não conseguem autenticar nem identificar atores

---

### Rollback e Compensação

| Situação | Compensação |
|---|---|
| Estoque insuficiente na criação da OS | OS marcada como `CANCELADA` (sem débito, pois a reserva é apenas lógica até a aprovação) |
| Orçamento reprovado pelo cliente | Produtos devolvidos ao estoque via `fila: orcamento_reprovado` |
| Falha na geração do link de pagamento | OS marcada como `ERRO_PAGAMENTO` via `fila: erro_pagamento` |

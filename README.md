# Sistema Barbearia — API RESTful

API RESTful para gerenciamento de barbearia, desenvolvida com Laravel 12. O sistema permite o cadastro de clientes, visualização da agenda de horários e gerenciamento de agendamentos, com notificações automáticas por e-mail para administradores.

---

## Requisitos

- PHP 8.2+
- Composer 2.x
- MySQL ou MariaDB

---

## Instalação e Configuração

### 1. Clonar o repositório

```bash
git clone https://github.com/LucasFerraz07/SistemaBarbearia-NextTecnologia.git
cd SistemaBarbearia-NextTecnologia
```

### 2. Instalar dependências

```bash
composer install
```

### 3. Configurar o ambiente

Copie o arquivo de exemplo e configure as variáveis de ambiente.

No Linux/Mac:
```bash
cp .env.example .env
```

No Windows:
```bash
copy .env.example .env
```

Em seguida gere a chave da aplicação:
```bash
php artisan key:generate
```

### 4. Configurar o banco de dados

Crie um banco de dados MySQL ou MariaDB e configure as variáveis no `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_barbearia
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 5. Configurar o serviço de e-mail

Configure as variáveis de e-mail no `.env`. Recomendamos o [Mailtrap](https://mailtrap.io) para ambiente de desenvolvimento:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_username_mailtrap
MAIL_PASSWORD=sua_senha_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@barbearia.com"
MAIL_FROM_NAME="Sistema Barbearia"
```

### 6. Configurar a fila de e-mails

```env
QUEUE_CONNECTION=database
```

> Para ver todas as variáveis de ambiente disponíveis, consulte o arquivo `.env.example` na raiz do projeto.

### 7. Rodar as migrations e seeds

```bash
php artisan migrate --seed
```

Isso criará todas as tabelas e o usuário administrador padrão:

| Campo | Valor |
|---|---|
| E-mail | admin@barbearia.com |
| Senha | password |

### 8. Iniciar o servidor

```bash
php artisan serve
```

### 9. Iniciar o worker de filas

Em um terminal separado. Sem esse comando os e-mails não serão enviados:

```bash
php artisan queue:work
```

---

## Estrutura do Banco de Dados

| Tabela | Descrição |
|---|---|
| `user_types` | Tipos de usuário (administrador, cliente) |
| `users` | Contas de acesso ao sistema |
| `cities` | Cidades normalizadas |
| `addresses` | Endereços vinculados às cidades |
| `clients` | Dados dos clientes |
| `schedulings` | Agendamentos |
| `personal_access_tokens` | Tokens de autenticação (Sanctum) |
| `jobs` | Fila de jobs para envio de e-mail |
| `job_batches` | Lotes de jobs |
| `failed_jobs` | Jobs que falharam na execução |
| `sessions` | Sessões da aplicação |
| `cache` | Cache da aplicação |
| `cache_locks` | Locks de cache |
| `password_reset_tokens` | Tokens de redefinição de senha |
| `migrations` | Histórico de migrations |

---

## Autenticação

A API utiliza **Laravel Sanctum** para autenticação via token Bearer.

Após o login, inclua o token no header de todas as requisições protegidas:

```
Authorization: Bearer {seu_token}
```

---

## Endpoints

### Públicos (sem autenticação)

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/register` | Cadastro de cliente |
| POST | `/api/login` | Login |

### Autenticados

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/logout` | Logout |
| GET | `/api/admins` | Listar administradores (admin) |
| POST | `/api/admins` | Cadastrar administrador (admin) |
| GET | `/api/admins/{id}` | Buscar administrador (admin) |
| PUT | `/api/admins/{id}` | Atualizar administrador (admin) |
| DELETE | `/api/admins/{id}` | Remover administrador (admin) |
| GET | `/api/clients` | Listar clientes (admin) |
| GET | `/api/clients/{id}` | Buscar cliente (admin ou dono) |
| PUT | `/api/clients/{id}` | Atualizar cliente (admin ou dono) |
| DELETE | `/api/clients/{id}` | Remover cliente (admin ou dono) |
| GET | `/api/schedulings` | Listar agendamentos |
| POST | `/api/schedulings` | Criar agendamento |
| GET | `/api/schedulings/{id}` | Buscar agendamento (admin ou dono) |
| PUT | `/api/schedulings/{id}` | Atualizar agendamento (admin ou dono) |
| DELETE | `/api/schedulings/{id}` | Remover agendamento (admin ou dono) |

### Filtros disponíveis em GET /api/schedulings

| Parâmetro | Descrição |
|---|---|
| `client_id` | Filtrar por cliente (admin apenas; clientes sempre filtram pelos próprios) |
| `start_date` | Filtrar agendamentos a partir de uma data |
| `end_date` | Filtrar agendamentos até uma data |

---

## Notificações por E-mail

Os administradores recebem notificações por e-mail nos seguintes eventos:

| Evento | Descrição |
|---|---|
| Novo agendamento | Quando um cliente cria um agendamento |
| Agendamento atualizado | Quando um agendamento é remarcado |
| Agendamento cancelado | Quando um agendamento é removido |

> Os e-mails de atualização e cancelamento foram implementados como decisão de negócio além do requisito mínimo, visando manter o administrador sempre informado sobre mudanças na agenda.

---

## Requisitos Implementados

### Obrigatórios
- [x] Laravel 12
- [x] Banco de dados MySQL
- [x] Autenticação via Laravel Sanctum
- [x] CRUD de Administradores
- [x] CRUD de Clientes
- [x] CRUD de Agendamentos
- [x] Envio de e-mail via SMTP ao criar agendamento
- [x] Seeder do usuário administrador padrão
- [x] Apenas administradores podem cadastrar novos administradores
- [x] Documentação via Swagger (L5-Swagger) e Apidog

### Diferenciais
- [x] Service Classes para separação da lógica de negócio
- [x] Paginação na listagem de clientes, administradores e agendamentos
- [x] Filtros de data e cliente nos agendamentos
- [x] FormRequest para validação de dados
- [x] Fila para envio de e-mail de notificação

### Extras (além do escopo)
- [x] Normalização do banco de dados com tabelas `cities` e `addresses`
- [x] Integração com API ViaCEP para preenchimento automático de endereço
- [x] Prevenção de conflito de horários com lock pessimista (race condition)
- [x] Notificações por e-mail nos eventos de atualização e cancelamento
- [x] Duração fixa de 1h30 por agendamento

---

## Documentação da API

A documentação foi gerada com **L5-Swagger** e está disponível com o servidor rodando em:

```
http://localhost:8000/api/documentation
```

### Gerar documentação

```bash
php artisan l5-swagger:generate
```

### Importar no Apidog

1. Acesse [apidog.com](https://apidog.com) e crie uma conta
2. Crie um novo projeto
3. Clique em **Import** e selecione **OpenAPI/Swagger**
4. Importe o arquivo gerado em `storage/api-docs/api-docs.json`
5. A documentação completa estará disponível

---

## Testando a API

Recomendamos o uso do **Postman** para testar os endpoints.

### Fluxo do Cliente

```
# 1. Cadastrar conta
POST /api/register
{"name": "João Silva", "email": "joao@email.com", "password": "password123", "phone": "12999999999", "cep": "01310100", "number": "1578"}

# 2. Fazer login
POST /api/login
{"email": "joao@email.com", "password": "password123"}

# 3. Visualizar horários ocupados do dia
GET /api/schedulings?start_date=2026-05-01&end_date=2026-05-01

# 4. Realizar agendamento
POST /api/schedulings
{"start_date": "2026-05-01 10:00:00"}

# 5. Visualizar os próprios agendamentos
# O client_id é retornado na resposta do cadastro ou do login, dentro do objeto "client"
GET /api/schedulings?client_id={seu_client_id}
```

### Fluxo do Administrador

```
# 1. Fazer login
POST /api/login
{"email": "admin@barbearia.com", "password": "password"}

# 2. Criar novo administrador
POST /api/admins
{"name": "Novo Admin", "email": "novoadmin@barbearia.com", "password": "password123"}

# 3. Listar todos os agendamentos
GET /api/schedulings
```

---

## Arquitetura do Projeto

```
app/
├── Http/
│   ├── Controllers/     # Recebem requisições e retornam respostas
│   ├── Middleware/      # EnsureUserIsAdmin
│   └── Requests/        # FormRequests para validação
├── Mail/                # Mailables para notificações
├── Models/              # Modelos Eloquent
├── Providers/           # Service Providers da aplicação
└── Services/            # Lógica de negócio
database/
├── migrations/          # Estrutura do banco de dados
└── seeders/             # Dados iniciais
resources/
└── views/emails/        # Templates de e-mail
routes/
└── api.php              # Rotas da API
```

---

Desenvolvido para o processo seletivo da **Next Tecnologia**.
# Fase 2 - Bootstrap Laravel 12 + Next.js 15 com shadcn/ui

> **Objetivo:** Instalar o Laravel real, configurar JWT auth, e montar o frontend com shadcn/ui.
> Ao final desta fase, teremos login funcional e dashboard admin com sidebar.

---

## Passo 2.1 - Instalar Laravel skeleton

O `backend/` tem apenas um `composer.json` e um placeholder. Precisamos instalar o Laravel completo.

**Por que nao usamos `composer create-project`?**
Porque o diretorio ja contem nosso `composer.json` customizado (com jwt-auth, kafka, pest, etc.). O `create-project` exige um diretorio vazio.

**Estrategia:** Criar um projeto Laravel temporario e copiar os arquivos skeleton para nosso diretorio.

```bash
# Entrar no container do backend
make backend-shell

# Dentro do container:
# 1. Criar Laravel em diretorio temporario
composer create-project laravel/laravel /tmp/laravel-skeleton --prefer-dist --no-interaction

# 2. Copiar arquivos skeleton (sem sobrescrever nosso composer.json)
cp /tmp/laravel-skeleton/artisan /var/www/html/
cp -r /tmp/laravel-skeleton/app /var/www/html/
cp -r /tmp/laravel-skeleton/bootstrap /var/www/html/
cp -r /tmp/laravel-skeleton/config /var/www/html/
cp -r /tmp/laravel-skeleton/database /var/www/html/
cp -r /tmp/laravel-skeleton/resources /var/www/html/
cp -r /tmp/laravel-skeleton/routes /var/www/html/
cp -r /tmp/laravel-skeleton/storage /var/www/html/
cp -r /tmp/laravel-skeleton/tests /var/www/html/
cp /tmp/laravel-skeleton/.env.example /var/www/html/
cp /tmp/laravel-skeleton/phpunit.xml /var/www/html/

# IMPORTANTE: sobrescrever o public/index.php placeholder com o real do Laravel
# Na Fase 1 criamos um index.php simples que retorna JSON fixo.
# Agora precisamos do index.php real que carrega o bootstrap do Laravel.
cp -f /tmp/laravel-skeleton/public/index.php /var/www/html/public/index.php

# Copiar .env.example para .env
cp /var/www/html/.env.example /var/www/html/.env

# 3. Limpar temporario
rm -rf /tmp/laravel-skeleton

# 4. Instalar NOSSAS dependencias (composer.json customizado)
composer install

# 5. Gerar chave da aplicacao
php artisan key:generate

# 6. Dar permissoes ao storage
chmod -R 777 storage bootstrap/cache

# 7. Sair do container
exit
```

**O que aconteceu:**
- O `composer create-project` baixou a versao mais recente do Laravel 12
- Copiamos toda a estrutura (models, config, routes, migrations, etc.)
- O `composer install` usou NOSSO `composer.json` que ja inclui `tymon/jwt-auth`, `laravel-kafka`, `pestphp/pest`, etc.
- O `key:generate` criou o `APP_KEY` no `.env`

**Conceito importante - Volumes no Docker:**
- `./backend:/var/www/html` = bind mount (codigo aparece no host)
- `backend-vendor:/var/www/html/vendor` = volume nomeado (fica SÓ no Docker, performance melhor)
- `backend-storage:/var/www/html/storage` = volume nomeado (persistente entre restarts)

Os arquivos do Laravel vao aparecer na pasta `backend/` do seu computador, mas `vendor/` e `storage/` ficam nos volumes Docker (por performance e persistencia).

---

## Passo 2.2 - Testar conexao com PostgreSQL e Redis

O `.env` ja foi criado no passo anterior. O docker-compose.yml ja passa as variaveis de ambiente (DB_HOST, REDIS_HOST, etc.) para o container, sobrescrevendo o `.env`.

Agora vamos testar a conexao com o PostgreSQL:

```bash
# Rodar as migrations padrao do Laravel
docker compose exec backend php artisan migrate
```

Deve exibir:
```
Migration table created successfully.
Running migrations...
   INFO  Running migrations.

  2024_... create_users_table .......... DONE
  2024_... create_password_reset_tokens_table .. DONE
  2024_... create_sessions_table ....... DONE
  2024_... create_cache_table .......... DONE
  2024_... create_jobs_table ........... DONE
```

**Verificar Redis:**
```bash
docker compose exec backend php artisan tinker
>>> Cache::put('test', 'Orderly funciona!', 60);
>>> Cache::get('test');
# Deve retornar: "Orderly funciona!"
>>> exit
```

**Conceito - Por que PostgreSQL e nao MySQL?**
- UUID nativo (gen_random_uuid())
- JSONB para dados semi-estruturados
- Full-text search embutido
- Melhor suporte em cloud (RDS, Cloud SQL, Supabase)
- Sequences e CTEs mais robustos

---

## Passo 2.3 - Configurar CORS e rotas API

O CORS (Cross-Origin Resource Sharing) permite que o frontend (`localhost:3000`) faca requests para o backend (`localhost/api`).

No Laravel 12, nao existe mais `config/cors.php`. O CORS e as rotas API sao configurados no `bootstrap/app.php`.

Primeiro, crie o arquivo de rotas API. Crie `backend/routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Orderly API v1',
        'version' => '1.0.0',
    ]);
});
```

Agora edite `backend/bootstrap/app.php` para registrar as rotas API e configurar o CORS:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

**O que mudou no Laravel 12?**
- Sem `config/cors.php` — o middleware `HandleCors` e adicionado automaticamente
- Sem `routes/api.php` por padrao — precisa criar e registrar no `bootstrap/app.php`
- A linha `api: __DIR__.'/../routes/api.php'` registra as rotas com prefixo `/api`
- O `trustProxies(at: '*')` e necessario porque o Nginx faz proxy para o PHP-FPM

Teste:
```bash
curl http://localhost/api
# Esperado: {"status":"ok","message":"Orderly API v1","version":"1.0.0"}
```

**Nota:** Na pratica, quando o frontend acessa `http://localhost/api` via Nginx, nao e cross-origin (mesma origem). Mas durante SSR, o Next.js chama o backend internamente pela rede Docker, e ai o CORS pode ser necessario.

---

## Corrigir permissoes dos arquivos do backend

Antes de continuar, precisamos corrigir as permissoes dos arquivos do backend. Como os arquivos foram criados dentro do container Docker (que roda como root), eles ficam com owner `root` no host, impedindo a edicao no VSCode ou outro editor.

```bash
# Corrigir permissoes via container Docker (nao precisa de sudo)
docker compose exec backend chown -R 1000:1000 /var/www/html
```

> **Por que isso acontece?** Quando voce roda comandos como `composer install` ou `php artisan` dentro do container, os arquivos sao criados pelo usuario root do container. Como usamos bind mount (`./backend:/var/www/html`), esses arquivos aparecem no host com owner root. O comando acima muda o owner para UID 1000 (seu usuario WSL).

> **Dica:** Sempre que rodar comandos dentro do container que criem ou modifiquem arquivos, rode o `chown` novamente para manter as permissoes corretas.

---

## Passo 2.4 - Configurar JWT Auth (tymon/jwt-auth)

JWT (JSON Web Token) e nosso metodo de autenticacao. Diferente do Sanctum (session-based), JWT e stateless e perfeito para APIs e microsservicos.

```bash
# Publicar a configuracao do JWT
docker compose exec backend php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# Gerar o JWT secret (adiciona JWT_SECRET no .env)
docker compose exec backend php artisan jwt:secret
```

Agora configure o guard de autenticacao. Edite `backend/config/auth.php`:

```php
<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
```

Agora modifique o model `User` para implementar `JWTSubject`. Edite `backend/app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // JWT: identificador unico do usuario no token
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    // JWT: claims customizados (ex: role, tenant_id)
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
```

**Conceito - JWT vs Sanctum vs Passport:**

| Feature | JWT | Sanctum | Passport |
|---|---|---|---|
| Stateless | Sim | Nao (session) | Sim (OAuth2) |
| Cloud-native | Perfeito | Limitado | Complexo |
| Simplicidade | Alta | Muito alta | Baixa |
| Refresh Token | Manual | N/A | Built-in |
| Microsservicos | Ideal | Nao adequado | Possivel |

Escolhemos JWT porque e stateless (nao precisa de sessao no Redis), funciona com qualquer frontend, e ideal para Kubernetes (horizontal scaling sem session affinity).

---

## Passo 2.5 - Clean Architecture - Padroes base

Antes de criar os controllers, vamos estabelecer os padroes arquiteturais que usaremos em TODO o projeto.

**Estrutura de diretorios:**

```
backend/app/
├── Actions/           # Use Cases (logica de negocio pura)
│   └── Auth/
│       └── LoginAction.php
├── DTOs/              # Data Transfer Objects
│   └── Auth/
│       └── LoginDTO.php
├── Repositories/      # Acesso a dados
│   ├── Contracts/     # Interfaces
│   │   └── UserRepositoryInterface.php
│   └── Eloquent/      # Implementacoes Eloquent
│       └── UserRepository.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/    # Versionamento de API
│   │           └── Auth/
│   │               └── AuthController.php
│   ├── Requests/
│   │   └── Auth/
│   │       └── LoginRequest.php
│   └── Resources/
│       └── UserResource.php
└── Providers/
    └── RepositoryServiceProvider.php
```

**Por que essa estrutura?**
- **Actions:** Cada classe = 1 caso de uso. Sem dependencias do framework. Testavel isoladamente.
- **DTOs:** Objetos imutaveis para transferir dados entre camadas. Sem logica.
- **Repositories:** Abstrai o acesso ao banco. Se trocar Eloquent por outro ORM, so muda a implementacao.
- **Versionamento (V1):** Permite evoluir a API sem quebrar clientes antigos.

### LoginDTO

Crie o arquivo `backend/app/DTOs/Auth/LoginDTO.php`:

```php
<?php

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\LoginRequest;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
```

**Conceito - `readonly class` (PHP 8.2+):**
Todas as propriedades sao automaticamente readonly. Impossivel modificar apos criacao. Perfeito para DTOs.

### LoginAction

Crie o arquivo `backend/app/Actions/Auth/LoginAction.php`:

```php
<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginDTO;
use Illuminate\Auth\AuthenticationException;

final class LoginAction
{
    public function execute(LoginDTO $dto): string
    {
        $token = auth('api')->attempt([
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        if (!$token) {
            throw new AuthenticationException('Credenciais invalidas.');
        }

        return $token;
    }
}
```

**Conceito - Action Pattern:**
- Recebe um DTO (dados validados)
- Executa a logica de negocio
- Retorna resultado ou lanca excecao
- Sem dependencia do HTTP (pode ser chamado por CLI, Queue, etc.)

### UserRepositoryInterface

Crie o arquivo `backend/app/Repositories/Contracts/UserRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function create(array $data): User;
}
```

### UserRepository

Crie o arquivo `backend/app/Repositories/Eloquent/UserRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $model,
    ) {}

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }
}
```

### RepositoryServiceProvider

Crie o arquivo `backend/app/Providers/RepositoryServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
```

Registre o provider em `backend/bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
```

**Conceito - Dependency Inversion (SOLID):**
O controller depende da INTERFACE `UserRepositoryInterface`, nao da implementacao `UserRepository`. Se amanha trocarmos Eloquent por Doctrine, so mudamos o binding no ServiceProvider. Zero mudanca nos controllers e actions.

---

## Passo 2.6 - Controller de autenticacao + rotas

### LoginRequest

Crie `backend/app/Http/Requests/Auth/LoginRequest.php`:

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email e obrigatorio.',
            'email.email' => 'Informe um email valido.',
            'password.required' => 'A senha e obrigatoria.',
            'password.min' => 'A senha deve ter no minimo 6 caracteres.',
        ];
    }
}
```

### UserResource

Crie `backend/app/Http/Resources/UserResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### AuthController

Crie `backend/app/Http/Controllers/Api/V1/Auth/AuthController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\DTOs\Auth\LoginDTO;
use App\Actions\Auth\LoginAction;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        try {
            $token = $action->execute(LoginDTO::fromRequest($request));

            return $this->respondWithToken($token);
        } catch (AuthenticationException) {
            return response()->json([
                'message' => 'Credenciais invalidas.',
            ], 401);
        }
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth()->user()),
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = auth()->refresh();

        return $this->respondWithToken($token);
    }

    private function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}
```

### Rotas API

Edite `backend/routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT)
    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);
    });
});
```

**Conceito - Versionamento de API:**
Prefixamos com `v1` para poder criar `v2` no futuro sem quebrar clientes existentes. URLs ficam: `GET /api/v1/auth/me`, `POST /api/v1/auth/login`, etc.

---

## Passo 2.7 - Seeder de admin e teste da API

Crie `backend/database/seeders/AdminUserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@orderly.com'],
            [
                'name' => 'Admin Orderly',
                'email' => 'admin@orderly.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
```

Registre no `backend/database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
```

Execute:

```bash
# Rodar seed
docker compose exec backend php artisan db:seed

# Testar login via curl
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}'
```

> **Importante:** O header `-H "Accept: application/json"` e essencial em APIs Laravel. Sem ele, erros sao retornados como paginas HTML ao inves de JSON legivel.

Resposta esperada:
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

Teste a rota protegida:
```bash
# Copie o access_token da resposta anterior
curl http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Accept: application/json"
```

Resposta esperada:
```json
{
  "data": {
    "id": 1,
    "name": "Admin Orderly",
    "email": "admin@orderly.com",
    "created_at": "2026-03-04T..."
  }
}
```

Se chegou aqui, o backend esta 100% funcional com JWT auth!

---

## Passo 2.8 - Configurar Tailwind CSS v4

O Tailwind CSS v4 tem uma configuracao muito mais simples que o v3. Nao precisa de `tailwind.config.js`. Tudo e feito via CSS.

Crie `frontend/postcss.config.mjs`:

```js
/** @type {import('postcss-load-config').Config} */
const config = {
  plugins: {
    "@tailwindcss/postcss": {},
  },
};

export default config;
```

Crie `frontend/src/app/globals.css`:

```css
@import "tailwindcss";
```

Atualize `frontend/src/app/layout.tsx`:

```tsx
import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Orderly - Plataforma SaaS de Delivery",
  description: "Sistema completo de gestao para restaurantes e delivery",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR" className="dark">
      <body className="min-h-screen bg-background text-foreground antialiased">
        {children}
      </body>
    </html>
  );
}
```

**Conceito - Tailwind CSS v4 vs v3:**
- v3: Configuracao via `tailwind.config.js` (JS)
- v4: Configuracao via `@theme` no CSS (CSS-first)
- v4: Nao precisa de `content` paths (auto-detection)
- v4: Import simples com `@import "tailwindcss"`
- v4: 10x mais rapido que v3 (engine reescrita em Rust)

---

## Passo 2.9 - Inicializar shadcn/ui

O shadcn/ui nao e uma biblioteca de componentes tradicional. Ele COPIA os componentes para o seu projeto. Voce tem controle total sobre o codigo.

```bash
# Dentro do container frontend
docker compose exec frontend sh

# Inicializar shadcn/ui
npx shadcn@latest init

# Selecione:
# Base color: Zinc
```

O comando vai:
- Criar `components.json` (configuracao do shadcn)
- Atualizar `globals.css` com variaveis CSS para temas
- Criar `src/lib/utils.ts` com a funcao `cn()` (class merge)
- Instalar dependencias: `class-variance-authority`, `clsx`, `tailwind-merge`, `lucide-react`

Agora instale os componentes que vamos usar:

```bash
# Componentes de formulario
npx shadcn@latest add button input label card

# Componentes de layout
npx shadcn@latest add sidebar separator skeleton tooltip avatar dropdown-menu

# Sair do container
exit
```

**Conceito - shadcn/ui vs Material UI vs Chakra UI:**

| Feature | shadcn/ui | MUI | Chakra |
|---|---|---|---|
| Filosofia | Copy & own | Install & use | Install & use |
| Bundle size | Zero (so o que usa) | Grande | Medio |
| Customizacao | Total (e seu codigo) | Temas | Temas |
| Tailwind | Nativo | Nao | Nao |
| Server Components | Sim | Parcial | Nao |

shadcn/ui e ideal para Next.js 15 porque funciona perfeitamente com Server Components e Tailwind CSS.

---

## Corrigir permissoes dos arquivos do frontend

Assim como no backend, os arquivos criados dentro do container Docker ficam com owner `root`. Corrija antes de criar/editar arquivos do frontend:

```bash
# Corrigir permissoes via container Docker (nao precisa de sudo)
docker compose exec frontend chown -R 1000:1000 /app
```

> **Dica:** Sempre que rodar `npm install` ou `npx shadcn` dentro do container, rode o `chown` novamente.

---

## Passo 2.10 - Instalar dependencias do frontend

```bash
# Dentro do container frontend
docker compose exec frontend npm install zustand react-hook-form @hookform/resolvers zod
```

| Pacote | Funcao |
|---|---|
| `zustand` | Estado global (alternativa leve ao Redux) |
| `react-hook-form` | Formularios performaticos |
| `@hookform/resolvers` | Integra Zod com React Hook Form |
| `zod` | Validacao de schemas TypeScript-first |

---

## Passo 2.11 - API Client

Crie `frontend/src/lib/api.ts`:

```typescript
type RequestOptions = RequestInit & {
  isServer?: boolean;
};

// Client-side: vai pelo Nginx (mesma origem)
const PUBLIC_API_URL = process.env.NEXT_PUBLIC_API_URL || "/api";

// Server-side: vai pela rede Docker interna
const INTERNAL_API_URL =
  process.env.INTERNAL_API_URL || "http://nginx:80/api";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public data?: unknown,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

export async function apiClient<T>(
  endpoint: string,
  options: RequestOptions = {},
): Promise<T> {
  const { isServer = false, headers: customHeaders, ...fetchOptions } = options;
  const baseUrl = isServer ? INTERNAL_API_URL : PUBLIC_API_URL;

  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(customHeaders as Record<string, string>),
  };

  // Adicionar token JWT se disponivel (client-side)
  if (typeof window !== "undefined") {
    const token = localStorage.getItem("token");
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  const response = await fetch(`${baseUrl}${endpoint}`, {
    ...fetchOptions,
    headers,
  });

  if (!response.ok) {
    const data = await response.json().catch(() => null);
    throw new ApiError(response.status, data?.message || "Erro na requisicao", data);
  }

  return response.json();
}
```

**Conceito - Server-side vs Client-side API calls:**

```
Browser (Client-side):
  fetch("/api/v1/auth/me")  →  Nginx (:80)  →  PHP-FPM (:9000)

Next.js Container (Server-side / SSR):
  fetch("http://nginx:80/api/v1/auth/me")  →  Nginx (:80)  →  PHP-FPM (:9000)
```

O Next.js Server Components rodam DENTRO do container Docker. Eles nao podem acessar `localhost` (que aponta para o container do frontend). Por isso usamos `http://nginx:80/api` para SSR.

Adicione a variavel de ambiente no `docker-compose.yml`, na secao do frontend:

```yaml
  frontend:
    environment:
      INTERNAL_API_URL: http://nginx:80/api
```

---

## Passo 2.12 - Auth Store (Zustand)

Crie `frontend/src/stores/auth-store.ts`:

```typescript
import { create } from "zustand";
import { persist } from "zustand/middleware";
import { apiClient, ApiError } from "@/lib/api";

// Sync token com cookie para o middleware (server-side) conseguir ler
function setTokenCookie(token: string) {
  document.cookie = `token=${token}; path=/; max-age=${60 * 60}; SameSite=Lax`;
}

function removeTokenCookie() {
  document.cookie = "token=; path=/; max-age=0";
}

interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
  setToken: (token: string) => void;
  clear: () => void;
}

interface LoginResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
}

interface UserResponse {
  data: User;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      isAuthenticated: false,
      isLoading: false,

      setToken: (token: string) => {
        localStorage.setItem("token", token);
        setTokenCookie(token);
        set({ token, isAuthenticated: true });
      },

      login: async (email: string, password: string) => {
        set({ isLoading: true });
        try {
          const response = await apiClient<LoginResponse>("/v1/auth/login", {
            method: "POST",
            body: JSON.stringify({ email, password }),
          });

          localStorage.setItem("token", response.access_token);
          setTokenCookie(response.access_token);
          set({
            token: response.access_token,
            isAuthenticated: true,
            isLoading: false,
          });

          // Buscar dados do usuario
          await get().fetchUser();
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      fetchUser: async () => {
        try {
          const response = await apiClient<UserResponse>("/v1/auth/me");
          set({ user: response.data });
        } catch {
          get().clear();
        }
      },

      logout: async () => {
        try {
          await apiClient("/v1/auth/logout", { method: "POST" });
        } catch {
          // Limpar mesmo se der erro no backend
        }
        get().clear();
      },

      clear: () => {
        localStorage.removeItem("token");
        removeTokenCookie();
        set({ token: null, user: null, isAuthenticated: false });
      },
    }),
    {
      name: "auth-storage",
      partialize: (state) => ({ token: state.token }),
    },
  ),
);
```

> **Por que o cookie?** O middleware do Next.js roda no servidor (Edge Runtime) e nao tem acesso ao `localStorage`. Para que ele saiba se o usuario esta autenticado, sincronizamos o token JWT como cookie. O `localStorage` continua sendo a fonte primaria para o client-side.

**Conceito - Zustand vs Redux:**
- Zustand: ~1KB, zero boilerplate, hooks nativos
- Redux: ~7KB, actions/reducers/store, Redux Toolkit necessario
- Para nosso caso (auth + theme), Zustand e mais que suficiente

---

## Passo 2.13 - Pagina de Login

Crie `frontend/src/app/login/page.tsx`:

```tsx
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useAuthStore } from "@/stores/auth-store";
import { ApiError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

const loginSchema = z.object({
  email: z.string().email("Informe um email valido"),
  password: z.string().min(6, "A senha deve ter no minimo 6 caracteres"),
});

type LoginForm = z.infer<typeof loginSchema>;

export default function LoginPage() {
  const router = useRouter();
  const { login, isLoading } = useAuthStore();
  const [error, setError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginForm>({
    resolver: zodResolver(loginSchema),
  });

  const onSubmit = async (data: LoginForm) => {
    setError(null);
    try {
      await login(data.email, data.password);
      router.push("/dashboard");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Erro ao conectar com o servidor.");
      }
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/50 px-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl font-bold">Orderly</CardTitle>
          <CardDescription>
            Acesse sua conta para gerenciar seu restaurante
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            {error && (
              <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                {error}
              </div>
            )}

            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                placeholder="admin@orderly.com"
                {...register("email")}
              />
              {errors.email && (
                <p className="text-sm text-destructive">{errors.email.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Senha</Label>
              <Input
                id="password"
                type="password"
                placeholder="••••••••"
                {...register("password")}
              />
              {errors.password && (
                <p className="text-sm text-destructive">
                  {errors.password.message}
                </p>
              )}
            </div>

            <Button type="submit" className="w-full" disabled={isLoading}>
              {isLoading ? "Entrando..." : "Entrar"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
```

**Conceito - `"use client"` no Next.js 15:**
Por padrao, componentes no App Router sao Server Components (renderizados no servidor). Quando precisamos de interatividade (useState, useEffect, event handlers), marcamos com `"use client"`. A pagina de login precisa de client-side porque tem formulario com estado.

---

## Passo 2.14 - Layout admin com sidebar

Crie o route group `(admin)` para agrupar paginas que compartilham o layout com sidebar.

Crie `frontend/src/app/(admin)/layout.tsx`:

```tsx
"use client";

import { useEffect } from "react";
import { SidebarProvider, SidebarInset } from "@/components/ui/sidebar";
import { AppSidebar } from "@/components/app-sidebar";
import { AppHeader } from "@/components/app-header";
import { useAuthStore } from "@/stores/auth-store";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const token = useAuthStore((s) => s.token);
  const user = useAuthStore((s) => s.user);
  const fetchUser = useAuthStore((s) => s.fetchUser);

  useEffect(() => {
    if (token && !user) {
      fetchUser();
    }
  }, [token, user, fetchUser]);

  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <AppHeader />
        <main className="flex-1 p-6">{children}</main>
      </SidebarInset>
    </SidebarProvider>
  );
}
```

> **Por que `"use client"` e `fetchUser()`?** O Zustand so persiste o `token` no localStorage (via `partialize`). Ao dar F5, o `user` e `null` ate que `fetchUser()` recarregue os dados via API. Sem isso, componentes como a sidebar condicional (Passo 5.12) nao renderizam os grupos corretos.

Crie `frontend/src/app/(admin)/dashboard/page.tsx`:

```tsx
export default function DashboardPage() {
  return (
    <div>
      <h1 className="text-3xl font-bold">Dashboard</h1>
      <p className="mt-2 text-muted-foreground">
        Bem-vindo ao painel administrativo do Orderly.
      </p>

      <div className="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {[
          { title: "Pedidos Hoje", value: "0" },
          { title: "Faturamento", value: "R$ 0,00" },
          { title: "Clientes", value: "0" },
          { title: "Produtos", value: "0" },
        ].map((card) => (
          <div
            key={card.title}
            className="rounded-xl border bg-card p-6 shadow-sm"
          >
            <p className="text-sm text-muted-foreground">{card.title}</p>
            <p className="mt-1 text-2xl font-bold">{card.value}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
```

Crie `frontend/src/components/app-sidebar.tsx`:

```tsx
"use client";

import {
  LayoutDashboard,
  ShoppingBag,
  Users,
  UtensilsCrossed,
  QrCode,
  Star,
  Settings,
} from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";

const menuItems = [
  { title: "Dashboard", url: "/dashboard", icon: LayoutDashboard },
  { title: "Pedidos", url: "/orders", icon: ShoppingBag },
  { title: "Cardapio", url: "/products", icon: UtensilsCrossed },
  { title: "Clientes", url: "/customers", icon: Users },
  { title: "Mesas", url: "/tables", icon: QrCode },
  { title: "Avaliacoes", url: "/reviews", icon: Star },
  { title: "Configuracoes", url: "/settings", icon: Settings },
];

export function AppSidebar() {
  const pathname = usePathname();

  return (
    <Sidebar>
      <SidebarHeader className="border-b px-6 py-4">
        <h2 className="text-lg font-bold">Orderly</h2>
      </SidebarHeader>
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupLabel>Menu</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {menuItems.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild isActive={pathname === item.url}>
                    <Link href={item.url}>
                      <item.icon />
                      <span>{item.title}</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>
    </Sidebar>
  );
}
```

Crie `frontend/src/components/app-header.tsx`:

```tsx
"use client";

import { useAuthStore } from "@/stores/auth-store";
import { useRouter } from "next/navigation";
import { SidebarTrigger } from "@/components/ui/sidebar";
import { Separator } from "@/components/ui/separator";
import { Button } from "@/components/ui/button";
import { LogOut } from "lucide-react";

export function AppHeader() {
  const { user, logout } = useAuthStore();
  const router = useRouter();

  const handleLogout = async () => {
    await logout();
    router.push("/login");
  };

  return (
    <header className="flex h-14 items-center gap-4 border-b px-6">
      <SidebarTrigger />
      <Separator orientation="vertical" className="h-6" />
      <div className="flex flex-1 items-center justify-between">
        <h1 className="text-sm font-medium">Painel Administrativo</h1>
        <div className="flex items-center gap-4">
          <span className="text-sm text-muted-foreground">
            {user?.name || "Carregando..."}
          </span>
          <Button variant="ghost" size="icon" onClick={handleLogout}>
            <LogOut className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </header>
  );
}
```

**Conceito - Route Groups no Next.js 15:**
Pastas com parenteses `(admin)` agrupam rotas que compartilham layout SEM afetar a URL. A URL fica `/dashboard`, nao `/(admin)/dashboard`. Isso permite ter layouts diferentes para admin vs public.

---

## Passo 2.15 - Middleware de autenticacao (Next.js)

Crie `frontend/src/middleware.ts`:

```typescript
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const token = request.cookies.get("token")?.value;
  const { pathname } = request.nextUrl;

  const isLoginPage = pathname === "/login";
  const isProtectedRoute = pathname.startsWith("/dashboard") ||
    pathname.startsWith("/orders") ||
    pathname.startsWith("/products") ||
    pathname.startsWith("/customers") ||
    pathname.startsWith("/tables") ||
    pathname.startsWith("/reviews") ||
    pathname.startsWith("/settings");

  // Redirecionar para login se nao autenticado
  if (isProtectedRoute && !token) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  // Redirecionar para dashboard se ja autenticado
  if (isLoginPage && token) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/dashboard/:path*",
    "/orders/:path*",
    "/products/:path*",
    "/customers/:path*",
    "/tables/:path*",
    "/reviews/:path*",
    "/settings/:path*",
    "/login",
  ],
};
```

Atualize a home page `frontend/src/app/page.tsx` para redirecionar ao dashboard:

```tsx
import { redirect } from "next/navigation";

export default function HomePage() {
  redirect("/login");
}
```

---

## Passo 2.16 - Verificacao end-to-end

Agora vamos verificar tudo funciona de ponta a ponta.

### Reiniciar os servicos

```bash
docker compose down
docker compose up -d --build
```

### Testar backend

```bash
# 1. Health check
curl http://localhost/api/v1/auth/login -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}'

# Esperado: {"access_token":"eyJ...","token_type":"bearer","expires_in":3600}

# 2. Rota protegida (substitua o token)
curl http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"

# Esperado: {"data":{"id":1,"name":"Admin Orderly",...}}
```

### Testar frontend

1. Acesse `http://127.0.0.1` no navegador (use `127.0.0.1`, nao `localhost` - veja Troubleshooting)
2. Deve redirecionar para `/login`
3. Faca login com `admin@orderly.com` / `password`
4. Deve redirecionar para `/dashboard` com sidebar
5. Clique no icone de logout no header
6. Deve voltar para `/login`

---

## Resumo da Fase 2

**Arquivos criados/modificados no backend:**

```
backend/
├── app/
│   ├── Actions/Auth/LoginAction.php
│   ├── DTOs/Auth/LoginDTO.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/Auth/AuthController.php
│   │   ├── Requests/Auth/LoginRequest.php
│   │   └── Resources/UserResource.php
│   ├── Models/User.php (modificado - JWTSubject)
│   ├── Providers/RepositoryServiceProvider.php
│   └── Repositories/
│       ├── Contracts/UserRepositoryInterface.php
│       └── Eloquent/UserRepository.php
├── config/auth.php (modificado - JWT guard)
├── config/cors.php (modificado - frontend origin)
├── database/seeders/AdminUserSeeder.php
├── routes/api.php (modificado - rotas v1)
└── bootstrap/providers.php (modificado - RepositoryServiceProvider)
```

**Arquivos criados/modificados no frontend:**

```
frontend/
├── postcss.config.mjs
├── components.json (gerado pelo shadcn)
├── src/
│   ├── app/
│   │   ├── globals.css (Tailwind + shadcn theme)
│   │   ├── layout.tsx (modificado - imports CSS)
│   │   ├── page.tsx (modificado - redirect)
│   │   ├── login/page.tsx
│   │   └── (admin)/
│   │       ├── layout.tsx (client component — fetchUser on hydration)
│   │       └── dashboard/page.tsx
│   ├── components/
│   │   ├── ui/ (gerado pelo shadcn)
│   │   ├── app-sidebar.tsx
│   │   └── app-header.tsx
│   ├── lib/
│   │   ├── api.ts
│   │   └── utils.ts (gerado pelo shadcn)
│   ├── stores/
│   │   └── auth-store.ts
│   └── middleware.ts
```

**Conceitos aprendidos:**
- JWT Authentication (stateless, cloud-native)
- Clean Architecture Pragmatica (Actions, DTOs, Repositories)
- Dependency Inversion (interfaces + ServiceProvider)
- API versioning (v1 prefix)
- Tailwind CSS v4 (CSS-first config)
- shadcn/ui (copy & own components)
- Zustand (lightweight state management)
- React Hook Form + Zod (type-safe forms)
- Next.js Route Groups (admin layout)
- Next.js Middleware (auth protection)
- Server-side vs Client-side API calls

**Proximo:** Fase 3 - Multi-tenancy + Planos de Assinatura

---


---

[Voltar ao README](../README.md)

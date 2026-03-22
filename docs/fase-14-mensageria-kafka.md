# Fase 14 - Mensageria com Apache Kafka

> Desacoplar servicos e processar eventos de forma assincrona. Quando um pedido muda de status, o sistema publica um evento no Kafka — consumers processam em background sem bloquear a request HTTP.

**Objetivo:** Implementar o padrao event-driven com Apache Kafka, criando producers e consumers para eventos de negocio do Orderly.

**O que voce vai aprender:**
- Apache Kafka: conceitos (topics, partitions, consumers, producers)
- Producer: publicar eventos de negocio no Kafka
- Consumer: processar eventos em background
- Retry e Dead Letter Queue (DLQ): tratamento de falhas
- Monitoramento de consumers e topics
- Testes de integracao com Kafka

**Pre-requisitos:**
- Fases 1-13 completas
- Docker Compose funcionando (Kafka ja esta configurado desde a Fase 1)
- `composer require mateusjunges/laravel-kafka` ja instalado

---

## Passo 14.1 - Conceito: Mensageria e Event-Driven Architecture

### Por que mensageria?

Sem mensageria (sincrono):
```
Cliente → API (cria pedido) → salva no banco → envia email → notifica cozinha → responde 201
                                                 ↑ tudo sincrono, request lenta (2-5s)
```

Com mensageria (assincrono):
```
Cliente → API (cria pedido) → salva no banco → publica evento → responde 201 (50ms)
                                                     ↓
                                              Kafka (broker)
                                              ↓           ↓
                                        Consumer A    Consumer B
                                        (email)       (notifica cozinha)
```

### Conceitos do Kafka

| Conceito | Analogia | Descricao |
|----------|----------|-----------|
| **Topic** | Canal do Slack | Fila nomeada onde mensagens sao publicadas |
| **Producer** | Quem envia mensagem | Publica eventos em um topic |
| **Consumer** | Quem le mensagem | Processa eventos de um topic |
| **Consumer Group** | Equipe | Grupo de consumers que dividem o trabalho |
| **Partition** | Sub-fila | Divisao de um topic para paralelismo |
| **Offset** | Bookmark | Posicao de leitura do consumer no topic |
| **Broker** | Servidor Kafka | Instancia do Kafka que armazena mensagens |

### Fluxo de um evento no Orderly

```
┌──────────────┐     ┌───────────────────────────────────────────┐
│   API HTTP   │     │              Apache Kafka                  │
│              │     │                                            │
│ POST /orders │────►│  Topic: orderly.orders.created             │
│              │     │  Topic: orderly.orders.status-changed      │
│ PUT /orders  │────►│  Topic: orderly.orders.cancelled           │
│   /{id}      │     │                                            │
└──────────────┘     └────────────┬──────────────┬───────────────┘
                                  │              │
                           ┌──────▼──────┐ ┌─────▼──────┐
                           │ Consumer A  │ │ Consumer B │
                           │ Logging &   │ │ Dashboard  │
                           │ Metricas    │ │ Realtime   │
                           └─────────────┘ └────────────┘
```

### Por que Kafka (e nao Redis Queue / RabbitMQ)?

| Criterio | Kafka | Redis Queue | RabbitMQ |
|----------|-------|-------------|----------|
| Persistencia | Disco (7+ dias) | Memoria (volatil) | Disco |
| Replay | Sim (re-ler eventos) | Nao | Nao |
| Throughput | Milhoes/s | Milhares/s | Milhares/s |
| Multiplos consumers | Sim (consumer groups) | Nao | Sim |
| Ordering | Por particao | FIFO simples | Por fila |
| Complexidade | Alta | Baixa | Media |
| Uso ideal | Event sourcing, streaming | Jobs simples, cache | Task queues |

**Para portfolio:** Kafka mostra que voce sabe trabalhar com arquitetura event-driven e sistemas distribuidos — um diferencial forte em entrevistas.

> **Nota:** O Orderly ja tem o Kafka configurado no Docker desde a Fase 1 (imagem `apache/kafka:4.2.0` em KRaft mode). O pacote `mateusjunges/laravel-kafka` tambem ja esta no `composer.json`. Nesta fase vamos apenas implementar os eventos de negocio.

---

## Passo 14.2 - Publicar o config do Laravel Kafka

### Publicar configuracao

```bash
docker compose exec backend php artisan vendor:publish --provider="Junges\Kafka\Providers\LaravelKafkaServiceProvider"
sudo chown -R $USER:$USER backend/config/
```

Isso cria o arquivo `backend/config/kafka.php`.

### Verificar variaveis de ambiente

As variaveis ja devem estar no `.env`:

```env
KAFKA_BROKERS=kafka:9092
KAFKA_GROUP_ID=orderly-group
```

Se nao estiverem, adicione-as ao `backend/.env` e ao `backend/.env.example`.

### Verificar conectividade

```bash
# Verificar se o Kafka esta rodando e saudavel
docker compose exec kafka bash -c "echo 'Kafka is running'"

# Listar topics existentes (pode estar vazio)
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server localhost:9092 --list

# Criar um topic de teste
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --create \
  --topic test.ping \
  --partitions 1 \
  --replication-factor 1

# Verificar que foi criado
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server localhost:9092 --list
# test.ping
```

> **Dica:** O Kafka UI esta disponivel em `http://127.0.0.1:8080` quando rodando com `make up-monitoring`. La voce pode visualizar topics, mensagens e consumers graficamente.

---

## Passo 14.3 - Definir os eventos de negocio

### Quais eventos publicar?

Analisando o dominio do Orderly, os eventos mais importantes sao:

| Evento | Quando acontece | Quem consome |
|--------|----------------|--------------|
| `order.created` | Novo pedido criado | Logging, metricas, notificacao |
| `order.status_changed` | Status muda (open→accepted, etc.) | Logging, metricas, notificacao |
| `order.cancelled` | Pedido rejeitado | Logging, metricas, estoque |
| `evaluation.created` | Cliente avalia pedido | Logging, metricas |

### Convencao de nomes de topics

```
orderly.{dominio}.{acao}

Exemplos:
orderly.orders.created
orderly.orders.status-changed
orderly.evaluations.created
```

### Criar a classe base de evento

```bash
docker compose exec backend mkdir -p app/Kafka/Events
sudo chown -R $USER:$USER backend/app/Kafka/
```

**`backend/app/Kafka/Events/KafkaEvent.php`**:
```php
<?php

namespace App\Kafka\Events;

use Illuminate\Support\Str;

abstract class KafkaEvent
{
    public readonly string $eventId;

    public readonly string $occurredAt;

    public function __construct()
    {
        $this->eventId = (string) Str::uuid();
        $this->occurredAt = now()->toISOString();
    }

    abstract public function topic(): string;

    abstract public function key(): string;

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => class_basename($this),
            'occurred_at' => $this->occurredAt,
        ];
    }
}
```

**Por que uma classe base?** Garante que todo evento tem `event_id` (idempotencia), `event_type` (identifica o evento) e `occurred_at` (auditoria). Campos que todo evento precisa.

### Criar os eventos concretos

**`backend/app/Kafka/Events/OrderCreatedEvent.php`**:
```php
<?php

namespace App\Kafka\Events;

use App\Models\Order;

class OrderCreatedEvent extends KafkaEvent
{
    public function __construct(
        public readonly Order $order,
    ) {
        parent::__construct();
    }

    public function topic(): string
    {
        return 'orderly.orders.created';
    }

    public function key(): string
    {
        return (string) $this->order->id;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'order' => [
                'id' => $this->order->id,
                'uuid' => $this->order->uuid,
                'identify' => $this->order->identify,
                'status' => $this->order->status,
                'total' => (float) $this->order->total,
                'tenant_id' => $this->order->tenant_id,
                'client_id' => $this->order->client_id,
                'table_id' => $this->order->table_id,
                'products_count' => $this->order->products->count(),
                'created_at' => $this->order->created_at->toISOString(),
            ],
        ]);
    }
}
```

**`backend/app/Kafka/Events/OrderStatusChangedEvent.php`**:
```php
<?php

namespace App\Kafka\Events;

use App\Models\Order;

class OrderStatusChangedEvent extends KafkaEvent
{
    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
    ) {
        parent::__construct();
    }

    public function topic(): string
    {
        return 'orderly.orders.status-changed';
    }

    public function key(): string
    {
        return (string) $this->order->id;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'order' => [
                'id' => $this->order->id,
                'uuid' => $this->order->uuid,
                'identify' => $this->order->identify,
                'previous_status' => $this->previousStatus,
                'new_status' => $this->order->status,
                'tenant_id' => $this->order->tenant_id,
            ],
        ]);
    }
}
```

**`backend/app/Kafka/Events/EvaluationCreatedEvent.php`**:
```php
<?php

namespace App\Kafka\Events;

use App\Models\Evaluation;

class EvaluationCreatedEvent extends KafkaEvent
{
    public function __construct(
        public readonly Evaluation $evaluation,
    ) {
        parent::__construct();
    }

    public function topic(): string
    {
        return 'orderly.evaluations.created';
    }

    public function key(): string
    {
        return (string) $this->evaluation->order_id;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'evaluation' => [
                'id' => $this->evaluation->id,
                'order_id' => $this->evaluation->order_id,
                'stars' => $this->evaluation->stars,
                'comment' => $this->evaluation->comment,
                'client_id' => $this->evaluation->client_id,
                'created_at' => $this->evaluation->created_at->toISOString(),
            ],
        ]);
    }
}
```

---

## Passo 14.4 - Criar o Producer (publicador de eventos)

### Por que um producer centralizado?

Em vez de chamar o Kafka diretamente em cada Action, criamos um servico producer que:
- Centraliza a logica de publicacao
- Facilita mocking em testes
- Adiciona logging automatico
- Trata falhas de conexao

### Criar o KafkaProducer

```bash
docker compose exec backend mkdir -p app/Kafka/Producers
sudo chown -R $USER:$USER backend/app/Kafka/
```

**`backend/app/Kafka/Producers/KafkaProducer.php`**:
```php
<?php

namespace App\Kafka\Producers;

use App\Kafka\Events\KafkaEvent;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;

class KafkaProducer
{
    public function publish(KafkaEvent $event): void
    {
        try {
            Kafka::publish()
                ->onTopic($event->topic())
                ->withBodyKey('data', $event->toArray())
                ->withHeaders([
                    'event_type' => class_basename($event),
                    'event_id' => $event->eventId,
                    'occurred_at' => $event->occurredAt,
                    'source' => 'orderly-backend',
                ])
                ->withKafkaKey($event->key())
                ->send();

            Log::info('Kafka event published', [
                'topic' => $event->topic(),
                'event_type' => class_basename($event),
                'event_id' => $event->eventId,
                'key' => $event->key(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish Kafka event', [
                'topic' => $event->topic(),
                'event_type' => class_basename($event),
                'error' => $e->getMessage(),
            ]);

            // Nao lanca excecao — o evento e best-effort
            // Em producao, considere salvar em uma tabela "outbox" para retry
        }
    }
}
```

**Por que nao lancar excecao?** A publicacao no Kafka e *best-effort* — se o Kafka estiver fora, o pedido ainda deve ser criado. O log do erro permite monitorar e investigar. Em producao, o padrao **Transactional Outbox** resolve isso de forma robusta (salva o evento no banco junto com a entidade e um worker publica depois).

### Registrar no Service Container

**`backend/app/Providers/AppServiceProvider.php`** — adicionar no metodo `register`:

```php
use App\Kafka\Producers\KafkaProducer;

public function register(): void
{
    $this->app->singleton(KafkaProducer::class);
}
```

---

## Passo 14.5 - Integrar o Producer nas Actions

Agora vamos publicar eventos quando pedidos sao criados e quando o status muda.

### CreateOrderAction

**`backend/app/Actions/Order/CreateOrderAction.php`** — adicionar publicacao do evento:

```php
<?php

namespace App\Actions\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\Kafka\Events\OrderCreatedEvent;
use App\Kafka\Producers\KafkaProducer;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final class CreateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly KafkaProducer $producer,
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        $order = $this->repository->create([
            'tenant_id' => $dto->tenantId,
            'table_id' => $dto->tableId,
            'client_id' => $dto->clientId,
            'status' => Order::STATUS_OPEN,
            'description' => $dto->description,
            'total' => 0,
        ]);

        $totalPrice = 0;
        $syncData = [];

        foreach ($dto->products as $product) {
            $syncData[$product['id']] = [
                'quantity' => $product['quantity'],
                'price' => $product['price'],
            ];
            $totalPrice += $product['quantity'] * $product['price'];
        }

        $order->products()->sync($syncData);
        $order->update(['total' => $totalPrice]);
        $order->load('products', 'table');

        // Publicar evento no Kafka
        $this->producer->publish(new OrderCreatedEvent($order));

        return $order;
    }
}
```

### UpdateOrderStatusAction

**`backend/app/Actions/Order/UpdateOrderStatusAction.php`** — adicionar publicacao do evento:

```php
<?php

namespace App\Actions\Order;

use App\DTOs\Order\UpdateOrderStatusDTO;
use App\Kafka\Events\OrderStatusChangedEvent;
use App\Kafka\Producers\KafkaProducer;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final class UpdateOrderStatusAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly KafkaProducer $producer,
    ) {}

    /**
     * @return Order|string Order atualizado ou mensagem de erro
     */
    public function execute(int $id, UpdateOrderStatusDTO $dto): Order|string
    {
        $order = $this->repository->findById($id);

        if (! $order) {
            return 'Pedido nao encontrado.';
        }

        if (! $order->canTransitionTo($dto->status)) {
            return "Transicao de '{$order->status}' para '{$dto->status}' nao e permitida.";
        }

        $previousStatus = $order->status;

        $this->repository->update($id, ['status' => $dto->status]);

        $order = $order->fresh(['products', 'table']);

        // Publicar evento no Kafka
        $this->producer->publish(new OrderStatusChangedEvent($order, $previousStatus));

        return $order;
    }
}
```

### Testar a publicacao

> **Pre-requisito:** Certifique-se de que os seeders foram executados para ter dados no banco (produtos, mesas, usuario admin):
> ```bash
> docker compose exec backend php artisan db:seed
> ```

```bash
# 1. Verificar que o Kafka esta rodando
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server localhost:9092 --list

# 2. Obter token JWT (use gerente@demo.com — o admin nao tem tenant_id)
TOKEN=$(docker compose exec nginx curl -s http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# 3. Verificar IDs de produtos disponiveis
docker compose exec nginx curl -s http://localhost/api/v1/products \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | grep -o '"id":[0-9]*' | head -5
# Use um dos IDs retornados no comando abaixo (ex: 3)

# 4. Criar um pedido (dispara OrderCreatedEvent)
# Substitua o product_id pelo ID retornado no passo anterior
docker compose exec nginx curl -s http://localhost/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"table_id":1,"products":[{"product_id":3,"qty":2}]}'

# 5. Verificar que o topic foi criado automaticamente
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server localhost:9092 --list
# orderly.orders.created

# 5. Ler as mensagens do topic
docker compose exec kafka /opt/kafka/bin/kafka-console-consumer.sh \
  --bootstrap-server localhost:9092 \
  --topic orderly.orders.created \
  --from-beginning \
  --max-messages 1

# 6. Ver nos logs do backend
docker compose logs backend --tail 5 | grep "Kafka event published"
```

> **Kafka UI:** Voce tambem pode ver as mensagens graficamente em `http://127.0.0.1:8080` → Topics → `orderly.orders.created` → Messages.

---

## Passo 14.6 - Criar Consumers (processadores de eventos)

### Conceito: Consumer vs Listener

| Conceito | Descricao |
|----------|-----------|
| **Consumer** | Processo que roda em loop, lendo mensagens do Kafka |
| **Handler** | Funcao que processa cada mensagem individualmente |
| **Consumer Group** | Grupo de consumers que dividem as particoes de um topic |

### Criar o handler de log de eventos

```bash
docker compose exec backend mkdir -p app/Kafka/Consumers
sudo chown -R $USER:$USER backend/app/Kafka/
```

**`backend/app/Kafka/Consumers/OrderEventsHandler.php`**:
```php
<?php

namespace App\Kafka\Consumers;

use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

class OrderEventsHandler implements Handler
{
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $body = $message->getBody();
        $headers = $message->getHeaders();
        $topic = $message->getTopicName();

        $eventType = $headers['event_type'] ?? 'unknown';
        $data = $body['data'] ?? [];

        Log::channel('stderr')->info("Kafka event consumed", [
            'topic' => $topic,
            'event_type' => $eventType,
            'event_id' => $data['event_id'] ?? null,
            'order_id' => $data['order']['id'] ?? null,
        ]);

        match ($eventType) {
            'OrderCreatedEvent' => $this->handleOrderCreated($data),
            'OrderStatusChangedEvent' => $this->handleOrderStatusChanged($data),
            default => Log::channel('stderr')->warning("Unknown event type: {$eventType}"),
        };
    }

    private function handleOrderCreated(array $data): void
    {
        $order = $data['order'] ?? [];

        Log::channel('stderr')->info("New order received", [
            'identify' => $order['identify'] ?? null,
            'total' => $order['total'] ?? 0,
            'products_count' => $order['products_count'] ?? 0,
        ]);

        // Aqui voce pode adicionar logica de negocio:
        // - Enviar notificacao push para a cozinha
        // - Atualizar dashboard em tempo real (via WebSocket)
        // - Enviar email de confirmacao ao cliente
    }

    private function handleOrderStatusChanged(array $data): void
    {
        $order = $data['order'] ?? [];

        Log::channel('stderr')->info("Order status changed", [
            'identify' => $order['identify'] ?? null,
            'from' => $order['previous_status'] ?? null,
            'to' => $order['new_status'] ?? null,
        ]);

        // Aqui voce pode adicionar logica de negocio:
        // - Notificar cliente que o pedido esta sendo preparado
        // - Atualizar metricas de tempo medio por status
        // - Disparar alerta se pedido ficou muito tempo em "accepted"
    }
}
```

### Criar o comando Artisan para rodar o consumer

```bash
docker compose exec backend php artisan make:command ConsumeOrderEvents
sudo chown -R $USER:$USER backend/app/Console/
```

**`backend/app/Console/Commands/ConsumeOrderEvents.php`**:
```php
<?php

namespace App\Console\Commands;

use App\Kafka\Consumers\OrderEventsHandler;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class ConsumeOrderEvents extends Command
{
    protected $signature = 'kafka:consume-orders';

    protected $description = 'Consume order events from Kafka topics';

    public function handle(): int
    {
        $this->info('Starting Kafka consumer for order events...');
        $this->info('Topics: orderly.orders.created, orderly.orders.status-changed');
        $this->info('Group: orderly-orders-consumer');
        $this->info('Press Ctrl+C to stop.');
        $this->newLine();

        try {
            Kafka::consumer()
                ->subscribe([
                    'orderly.orders.created',
                    'orderly.orders.status-changed',
                ])
                ->withConsumerGroupId('orderly-orders-consumer')
                ->withHandler(new OrderEventsHandler)
                ->withAutoCommit()
                ->build()
                ->consume();
        } catch (\Exception $e) {
            $this->error("Consumer error: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
```

### Testar o consumer

Abra **dois terminais**:

**Terminal 1 — Consumer (fica escutando):**
```bash
docker compose exec backend php artisan kafka:consume-orders
# Starting Kafka consumer for order events...
# Topics: orderly.orders.created, orderly.orders.status-changed
# Group: orderly-orders-consumer
# Press Ctrl+C to stop.
```

**Terminal 2 — Produzir eventos (criar/atualizar pedidos):**
```bash
# Obter token (use gerente@demo.com — tem tenant_id vinculado)
TOKEN=$(docker compose exec nginx curl -s http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Criar pedido (dispara order.created)
docker compose exec nginx curl -s http://localhost/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"table_id":1,"products":[{"product_id":3,"qty":1}]}'

# Atualizar status (dispara order.status_changed)
# Primeiro, pegue o ID do pedido criado acima:
ORDER_ID=$(docker compose exec nginx curl -s http://localhost/api/v1/orders \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | grep -o '"id":[0-9]*' | head -1 | grep -o '[0-9]*')
echo "Order ID: $ORDER_ID"

# Atualizar o status para "accepted"
docker compose exec nginx curl -s -X PUT http://localhost/api/v1/orders/$ORDER_ID \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"status":"accepted"}'
```

**No Terminal 1 voce deve ver:**
```
[INFO] Kafka event consumed {"topic":"orderly.orders.created","event_type":"OrderCreatedEvent",...}
[INFO] New order received {"identify":"ORD-000007","total":29.9,"products_count":1}

[INFO] Kafka event consumed {"topic":"orderly.orders.status-changed","event_type":"OrderStatusChangedEvent",...}
[INFO] Order status changed {"identify":"ORD-000007","from":"open","to":"accepted"}
```

---

## Passo 14.7 - Retry e Dead Letter Queue (DLQ)

### O que acontece quando o consumer falha?

Sem tratamento, uma mensagem que causa erro trava o consumer (fica tentando infinitamente). A solucao e:

1. **Retry** — tentar processar N vezes com delay crescente
2. **DLQ** — apos N falhas, mover para um topic especial de "mortos"

### Criar o handler com retry

**`backend/app/Kafka/Consumers/RetryableHandler.php`**:
```php
<?php

namespace App\Kafka\Consumers;

use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

class RetryableHandler implements Handler
{
    public function __construct(
        private readonly Handler $innerHandler,
        private readonly int $maxRetries = 3,
    ) {}

    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                ($this->innerHandler)($message, $consumer);

                return; // Sucesso, sai do loop
            } catch (\Exception $e) {
                $attempts++;
                $topic = $message->getTopicName();
                $headers = $message->getHeaders();
                $eventId = $headers['event_id'] ?? 'unknown';

                Log::channel('stderr')->warning("Kafka consumer retry", [
                    'topic' => $topic,
                    'event_id' => $eventId,
                    'attempt' => $attempts,
                    'max_retries' => $this->maxRetries,
                    'error' => $e->getMessage(),
                ]);

                if ($attempts >= $this->maxRetries) {
                    $this->sendToDlq($message, $e);

                    return;
                }

                // Backoff exponencial: 1s, 2s, 4s
                sleep(pow(2, $attempts - 1));
            }
        }
    }

    private function sendToDlq(ConsumerMessage $message, \Exception $error): void
    {
        $topic = $message->getTopicName();
        $dlqTopic = $topic . '.dlq';

        try {
            \Junges\Kafka\Facades\Kafka::publish()
                ->onTopic($dlqTopic)
                ->withBodyKey('original_message', $message->getBody())
                ->withHeaders(array_merge($message->getHeaders(), [
                    'dlq_reason' => $error->getMessage(),
                    'dlq_original_topic' => $topic,
                    'dlq_timestamp' => now()->toISOString(),
                ]))
                ->send();

            Log::channel('stderr')->error("Message sent to DLQ", [
                'original_topic' => $topic,
                'dlq_topic' => $dlqTopic,
                'error' => $error->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::channel('stderr')->critical("Failed to send message to DLQ", [
                'original_topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### Atualizar o comando para usar retry

**`backend/app/Console/Commands/ConsumeOrderEvents.php`** — atualizar o handle:

```php
public function handle(): int
{
    $this->info('Starting Kafka consumer for order events...');
    $this->info('Topics: orderly.orders.created, orderly.orders.status-changed');
    $this->info('Group: orderly-orders-consumer');
    $this->info('Retry: 3 attempts with exponential backoff');
    $this->info('DLQ: {topic}.dlq');
    $this->info('Press Ctrl+C to stop.');
    $this->newLine();

    try {
        $handler = new RetryableHandler(
            innerHandler: new OrderEventsHandler,
            maxRetries: 3,
        );

        Kafka::consumer()
            ->subscribe([
                'orderly.orders.created',
                'orderly.orders.status-changed',
            ])
            ->withConsumerGroupId('orderly-orders-consumer')
            ->withHandler($handler)
            ->withAutoCommit()
            ->build()
            ->consume();
    } catch (\Exception $e) {
        $this->error("Consumer error: {$e->getMessage()}");

        return self::FAILURE;
    }

    return self::SUCCESS;
}
```

Nao esqueca de adicionar o import no topo:
```php
use App\Kafka\Consumers\RetryableHandler;
```

### Fluxo de retry

```
Mensagem chega → Handler tenta processar
                  ├─ Sucesso → commit offset ✓
                  └─ Falha → retry (1s) → retry (2s) → retry (4s) → DLQ ✗
```

---

## Passo 14.8 - Monitoramento de Kafka

### Verificar topics e consumer groups

```bash
# Listar todos os topics
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh \
  --bootstrap-server localhost:9092 --list

# Detalhes de um topic (particoes, replicas, ISR)
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --describe \
  --topic orderly.orders.created

# Listar consumer groups
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh \
  --bootstrap-server localhost:9092 --list

# Ver lag de um consumer group (mensagens pendentes)
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh \
  --bootstrap-server localhost:9092 \
  --describe \
  --group orderly-orders-consumer
```

### Kafka UI

O Kafka UI (`http://127.0.0.1:8080`) permite visualizar:
- **Topics:** lista de topics, particoes, configuracoes
- **Messages:** conteudo das mensagens em cada topic
- **Consumers:** consumer groups, lag, offsets

> **Lembre:** O Kafka UI so sobe com `make up-monitoring` (profile `monitoring`).

### Adicionar targets ao Makefile

Adicionar ao `Makefile`:

```makefile
# ── Kafka ──────────────────────────────────────────────────────
kafka-topics: ## Listar topics do Kafka
	docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server localhost:9092 --list

kafka-consumers: ## Listar consumer groups e lag
	docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh --bootstrap-server localhost:9092 --list

kafka-consume-orders: ## Iniciar consumer de eventos de pedidos
	docker compose exec backend php artisan kafka:consume-orders
```

### Testar

```bash
# Ver topics criados
make kafka-topics

# Iniciar consumer em um terminal
make kafka-consume-orders

# Em outro terminal, criar pedidos e ver eventos sendo consumidos
```

---

## Passo 14.9 - Testes de integracao

### Mockar o KafkaProducer nos testes

Em testes, nao queremos depender do Kafka real. O `KafkaProducer` foi registrado como singleton, entao podemos mockar:

**`backend/tests/Feature/Api/OrderKafkaTest.php`**:

> **Nota:** Usamos a sintaxe Pest (consistente com os outros testes do projeto) e os helpers `createAdminUser()` + `authHeaders()` definidos em `tests/Pest.php`. O `createAdminUser()` cria um usuario super-admin que faz bypass do ACL. O payload de pedido usa `product_id`/`qty` (nao `id`/`quantity`). O `table_id` e nullable, entao nao precisamos de `TableFactory`.

```php
<?php

use App\Kafka\Events\OrderCreatedEvent;
use App\Kafka\Events\OrderStatusChangedEvent;
use App\Kafka\Producers\KafkaProducer;
use App\Models\Order;
use App\Models\Product;
use Mockery;

describe('Order Kafka Events', function () {
    it('publishes kafka event when creating order', function () {
        $user = createAdminUser();

        $mock = Mockery::mock(KafkaProducer::class);
        $mock->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderCreatedEvent::class));

        $this->app->instance(KafkaProducer::class, $mock);

        $product = Product::factory()->create(['tenant_id' => $user->tenant_id]);

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/orders', [
                'products' => [
                    ['product_id' => $product->id, 'qty' => 2],
                ],
            ]);

        $response->assertCreated();
    });

    it('publishes kafka event when updating order status', function () {
        $user = createAdminUser();

        $mock = Mockery::mock(KafkaProducer::class);
        $mock->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderStatusChangedEvent::class));

        $this->app->instance(KafkaProducer::class, $mock);

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'accepted',
            ]);

        $response->assertOk();
    });

    it('does not publish kafka event on rejected transition', function () {
        $user = createAdminUser();

        $mock = Mockery::mock(KafkaProducer::class);
        $mock->shouldNotReceive('publish');

        $this->app->instance(KafkaProducer::class, $mock);

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'open',
            ]);

        $response->assertUnprocessable();
    });
});
```

### Rodar os testes

```bash
docker compose exec backend php artisan test --filter=OrderKafkaTest
```

---

## Passo 14.10 - Resumo e proximos passos

### O que implementamos

| Componente | Funcao | Arquivo |
|-----------|--------|---------|
| **KafkaEvent** | Classe base de eventos | `app/Kafka/Events/KafkaEvent.php` |
| **OrderCreatedEvent** | Evento de pedido criado | `app/Kafka/Events/OrderCreatedEvent.php` |
| **OrderStatusChangedEvent** | Evento de mudanca de status | `app/Kafka/Events/OrderStatusChangedEvent.php` |
| **EvaluationCreatedEvent** | Evento de avaliacao criada | `app/Kafka/Events/EvaluationCreatedEvent.php` |
| **KafkaProducer** | Publicador centralizado | `app/Kafka/Producers/KafkaProducer.php` |
| **OrderEventsHandler** | Consumer de eventos de pedidos | `app/Kafka/Consumers/OrderEventsHandler.php` |
| **RetryableHandler** | Wrapper de retry + DLQ | `app/Kafka/Consumers/RetryableHandler.php` |
| **ConsumeOrderEvents** | Comando Artisan para consumer | `app/Console/Commands/ConsumeOrderEvents.php` |
| **OrderKafkaTest** | Testes de integracao | `tests/Feature/Api/OrderKafkaTest.php` |

### Arquitetura implementada

```
┌─────────────┐          ┌──────────────────────────────────┐
│   HTTP API  │          │         Apache Kafka              │
│             │          │                                    │
│ POST /orders│──publish──►  orderly.orders.created          │
│             │          │  orderly.orders.status-changed    │
│ PUT /orders │──publish──►  orderly.evaluations.created     │
│   /{id}     │          │                                    │
└─────────────┘          └────────┬───────────┬──────────────┘
                                  │           │
                           ┌──────▼──────┐    │
                           │  Consumer   │    │
                           │  (artisan   │    │
                           │  command)   │    │
                           └──────┬──────┘    │
                                  │           │
                           ┌──────▼──────┐    │
                           │  Retry (3x) │    │
                           │  Backoff    │    │
                           └──────┬──────┘    │
                                  │           │
                           ┌──────▼──────┐    │
                           │    DLQ      │    │
                           │  .dlq topic │    │
                           └─────────────┘    │
                                              │
                                       ┌──────▼──────┐
                                       │  Kafka UI   │
                                       │ :8080       │
                                       └─────────────┘
```

### Comandos uteis

```bash
# Listar topics
make kafka-topics

# Consumer de pedidos
make kafka-consume-orders

# Ver mensagens de um topic
docker compose exec kafka /opt/kafka/bin/kafka-console-consumer.sh \
  --bootstrap-server localhost:9092 \
  --topic orderly.orders.created \
  --from-beginning

# Ver mensagens na DLQ
docker compose exec kafka /opt/kafka/bin/kafka-console-consumer.sh \
  --bootstrap-server localhost:9092 \
  --topic orderly.orders.created.dlq \
  --from-beginning

# Ver lag dos consumers
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh \
  --bootstrap-server localhost:9092 \
  --describe \
  --group orderly-orders-consumer
```

### Arquivos criados/modificados

```
backend/config/kafka.php                              # Publicado via vendor:publish
backend/app/Kafka/Events/KafkaEvent.php               # Classe base de eventos
backend/app/Kafka/Events/OrderCreatedEvent.php        # Evento: pedido criado
backend/app/Kafka/Events/OrderStatusChangedEvent.php  # Evento: status mudou
backend/app/Kafka/Events/EvaluationCreatedEvent.php   # Evento: avaliacao criada
backend/app/Kafka/Producers/KafkaProducer.php         # Producer centralizado
backend/app/Kafka/Consumers/OrderEventsHandler.php    # Handler de eventos de pedidos
backend/app/Kafka/Consumers/RetryableHandler.php      # Retry + DLQ wrapper
backend/app/Console/Commands/ConsumeOrderEvents.php   # Comando artisan consumer
backend/app/Actions/Order/CreateOrderAction.php       # Modificado: publica evento
backend/app/Actions/Order/UpdateOrderStatusAction.php # Modificado: publica evento
backend/app/Providers/AppServiceProvider.php          # Registra KafkaProducer singleton
backend/tests/Feature/Api/OrderKafkaTest.php          # Testes com mock do producer
Makefile                                               # Targets: kafka-topics, kafka-consume-orders
```

### Proximos passos sugeridos

- **Consumer como servico Docker:** Adicionar um container dedicado para o consumer (roda `php artisan kafka:consume-orders` automaticamente)
- **Transactional Outbox:** Salvar eventos no banco antes de publicar (garante entrega mesmo se o Kafka estiver fora)
- **WebSockets:** Notificar o frontend em tempo real quando um pedido muda de status (via Laravel Reverb ou Pusher)
- **Event Sourcing:** Reconstruir estado a partir dos eventos (audit trail completo)
- **Schema Registry:** Validar formato das mensagens com Avro/Protobuf

<?php

namespace App\Models;

use App\Observers\OrderObserver;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    use HasFactory, BelongsToTenant;

    const STATUS_OPEN = 'open';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PREPARING = 'preparing';
    const STATUS_DONE = 'done';
    const STATUS_DELIVERED = 'delivered';

    const VALID_TRANSITIONS = [
        self::STATUS_OPEN => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
        self::STATUS_ACCEPTED => [self::STATUS_PREPARING, self::STATUS_REJECTED],
        self::STATUS_PREPARING => [self::STATUS_DONE],
        self::STATUS_DONE => [self::STATUS_DELIVERED],
    ];

    const ALL_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_PREPARING,
        self::STATUS_DONE,
        self::STATUS_DELIVERED,
    ];

    protected $fillable = [
        'tenant_id',
        'table_id',
        'client_id',
        'status',
        'total',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::VALID_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['qty', 'price']);
    }

    public function calculateTotal(): string
    {
        return $this->products->sum(function ($product) {
            return $product->pivot->qty * $product->pivot->price;
        });
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(OrderEvaluation::class);
    }
}
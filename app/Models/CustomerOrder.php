<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrder extends Model
{
    protected $table = 'customer_orders';
    protected $primaryKey = 'order_id';

    protected $fillable = [
        'restaurant_id',
        'order_number',
        'order_type',
        'status',
        'customer_name',
        'customer_phone',
        'customer_address',
        'table_number',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'delivery_fee',
        'total_amount',
        'notes',
        'order_time',
        'estimated_ready_time',
        'completed_at',
        'served_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'order_time' => 'datetime',
        'estimated_ready_time' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'order_type' => 'dine_in',
        'tax_amount' => 0,
        'discount_amount' => 0,
        'delivery_fee' => 0,
    ];

    /**
     * Get the order items for this order
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class, 'order_id', 'order_id');
    }

    /**
     * Get the restaurant for this order
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(RestaurantData::class, 'restaurant_id', 'id');
    }

    /**
     * Get the employee who served this order
     */
    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by', 'id');
    }

    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber(int $restaurantId): string
    {
        $prefix = 'ORD-' . str_pad($restaurantId, 3, '0', STR_PAD_LEFT) . '-';
        $date = now()->format('Ymd');
        $sequence = self::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total amount including tax and fees
     */
    public function calculateTotal(): void
    {
        $this->subtotal = $this->orderItems->sum('total_price');
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->delivery_fee - $this->discount_amount;
        $this->save();
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'served']);
    }

    /**
     * Get orders by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get orders for a specific restaurant
     */
    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    /**
     * Get today's orders
     */
    public function scopeToday($query)
    {
        return $query->whereDate('order_time', today());
    }

    /**
     * Get orders within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_time', [$startDate, $endDate]);
    }

    /**
     * Update order status and handle inventory deduction
     */
    public function updateStatus(string $newStatus): bool
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;

        // Deduct inventory when order is confirmed
        if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
            $this->deductInventoryForOrder();
        }

        // Complete order timestamp
        if (in_array($newStatus, ['completed', 'served'])) {
            $this->completed_at = now();
        }

        return $this->save();
    }

    /**
     * Deduct inventory for all items in the order
     */
    protected function deductInventoryForOrder(): void
    {
        $inventoryService = app(\App\Services\InventoryService::class);

        foreach ($this->orderItems as $orderItem) {
            if (!$orderItem->inventory_deducted) {
                try {
                    $inventoryService->subtractStockFromDishSale(
                        $orderItem->dish_id,
                        $orderItem->quantity
                    );

                    $orderItem->inventory_deducted = true;
                    $orderItem->inventory_deducted_at = now();
                    $orderItem->save();
                } catch (\Exception $e) {
                    \Log::error("Failed to deduct inventory for order item {$orderItem->order_item_id}: " . $e->getMessage());
                    throw $e;
                }
            }
        }
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrderItem extends Model
{
    protected $table = 'customer_order_items';
    protected $primaryKey = 'order_item_id';

    protected $fillable = [
        'order_id',
        'dish_id',
        'dish_name',
        'unit_price',
        'quantity',
        'total_price',
        'special_instructions',
        'status',
        'inventory_deducted',
        'inventory_deducted_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
        'inventory_deducted' => 'boolean',
        'inventory_deducted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'inventory_deducted' => false,
    ];

    /**
     * Get the order this item belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id', 'order_id');
    }

    /**
     * Get the dish for this order item
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    /**
     * Calculate total price based on quantity and unit price
     */
    public function calculateTotalPrice(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }

    /**
     * Get items by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get items that need inventory deduction
     */
    public function scopeNeedingInventoryDeduction($query)
    {
        return $query->where('inventory_deducted', false);
    }

    /**
     * Get items with inventory already deducted
     */
    public function scopeInventoryDeducted($query)
    {
        return $query->where('inventory_deducted', true);
    }

    /**
     * Create order item from dish and calculate pricing
     */
    public static function createFromDish(Dish $dish, int $quantity, string $priceType = 'dine_in', string $specialInstructions = null): self
    {
        // Get the appropriate pricing for the dish
        $pricing = $dish->pricing()->where('price_type', $priceType)->first();

        if (!$pricing) {
            throw new \Exception("No pricing found for dish {$dish->dish_name} with price type {$priceType}");
        }

        return new self([
            'dish_id' => $dish->dish_id,
            'dish_name' => $dish->dish_name,
            'unit_price' => $pricing->base_price,
            'quantity' => $quantity,
            'total_price' => $pricing->base_price * $quantity,
            'special_instructions' => $specialInstructions,
        ]);
    }

    /**
     * Update status and handle related order updates
     */
    public function updateStatus(string $newStatus): bool
    {
        $this->status = $newStatus;
        $result = $this->save();

        // Update parent order status if all items are ready
        if ($newStatus === 'ready') {
            $this->checkAndUpdateOrderStatus();
        }

        return $result;
    }

    /**
     * Check if all order items are ready and update order status
     */
    protected function checkAndUpdateOrderStatus(): void
    {
        $order = $this->order;
        $allItemsReady = $order->orderItems()
            ->where('status', '!=', 'ready')
            ->count() === 0;

        if ($allItemsReady && $order->status === 'preparing') {
            $order->updateStatus('ready');
        }
    }
}
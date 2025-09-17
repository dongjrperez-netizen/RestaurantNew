<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class KitchenInventory extends Model
{
    use HasFactory;

    protected $table = 'kitchen_inventories';
    protected $primaryKey = 'kitchen_stock_id';

    protected $fillable = [
        'restaurant_id',
        'ingredient_id',
        'stock_id',
        'quantity_transferred',
        'quantity_remaining',
        'unit',
        'transfer_date',
        'expiry_date',
        'transferred_by',
        'status',
        'transfer_notes',
    ];

    protected $casts = [
        'quantity_transferred' => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'transfer_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id', 'id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }

    public function stockInventory()
    {
        return $this->belongsTo(StockInventory::class, 'stock_id', 'stock_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by', 'id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('quantity_remaining', '>', 0);
    }

    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeForIngredient($query, $ingredientId)
    {
        return $query->where('ingredient_id', $ingredientId);
    }

    public function scopeExpiringWithin($query, $days = 7)
    {
        return $query->whereDate('expiry_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeExpired($query)
    {
        return $query->whereDate('expiry_date', '<', Carbon::now());
    }

    // Methods
    public function useQuantity($quantity, $notes = null)
    {
        if ($this->status !== 'active') {
            throw new \Exception('Kitchen inventory is not active');
        }

        if ($quantity > $this->quantity_remaining) {
            throw new \Exception('Cannot use more than remaining quantity');
        }

        $this->quantity_remaining -= $quantity;

        // If all quantity is used up, mark as used_up
        if ($this->quantity_remaining <= 0) {
            $this->status = 'used_up';
            $this->quantity_remaining = 0;
        }

        if ($notes) {
            $this->transfer_notes = $this->transfer_notes ? $this->transfer_notes . "\n" . $notes : $notes;
        }

        $this->save();

        return $this;
    }

    public function returnToStock($quantity, $notes = null)
    {
        if ($quantity > $this->getUsedQuantity()) {
            throw new \Exception('Cannot return more than was used');
        }

        // Add quantity back to kitchen inventory
        $this->quantity_remaining += $quantity;

        if ($this->status === 'used_up' && $this->quantity_remaining > 0) {
            $this->status = 'active';
        }

        // Add quantity back to stock inventory
        $stockInventory = $this->stockInventory;
        $stockInventory->quantity += $quantity;

        if ($stockInventory->status === 'transferred') {
            $stockInventory->status = 'available';
        }

        $stockInventory->save();

        if ($notes) {
            $this->transfer_notes = $this->transfer_notes ? $this->transfer_notes . "\nReturned: " . $notes : "Returned: " . $notes;
        }

        $this->save();

        return $this;
    }

    public function isExpired()
    {
        return $this->expiry_date && Carbon::now()->greaterThan($this->expiry_date);
    }

    public function isExpiringWithin($days = 7)
    {
        return $this->expiry_date && Carbon::now()->addDays($days)->greaterThanOrEqualTo($this->expiry_date);
    }

    public function getUsedQuantity()
    {
        return $this->quantity_transferred - $this->quantity_remaining;
    }

    public function getUsagePercentage()
    {
        if ($this->quantity_transferred <= 0) {
            return 0;
        }

        return ($this->getUsedQuantity() / $this->quantity_transferred) * 100;
    }

    public function markAsExpired()
    {
        $this->status = 'expired';
        $this->save();

        return $this;
    }

    // Automatically mark as expired if past expiry date
    public function checkAndMarkExpired()
    {
        if ($this->isExpired() && $this->status === 'active') {
            $this->markAsExpired();
        }

        return $this;
    }
}
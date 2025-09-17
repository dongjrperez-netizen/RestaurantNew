<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockInventory extends Model
{
    use HasFactory;

    protected $table = 'stock_inventories';
    protected $primaryKey = 'stock_id';

    protected $fillable = [
        'restaurant_id',
        'ingredient_id',
        'batch_number',
        'quantity',
        'unit',
        'cost_per_unit',
        'total_cost',
        'expiry_date',
        'received_date',
        'supplier_name',
        'purchase_order_number',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'received_date' => 'date',
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

    public function kitchenInventories()
    {
        return $this->hasMany(KitchenInventory::class, 'stock_id', 'stock_id');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeExpiringWithin($query, $days = 7)
    {
        return $query->whereDate('expiry_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeForIngredient($query, $ingredientId)
    {
        return $query->where('ingredient_id', $ingredientId);
    }

    // Methods
    public function isExpired()
    {
        return $this->expiry_date && Carbon::now()->greaterThan($this->expiry_date);
    }

    public function isExpiringWithin($days = 7)
    {
        return $this->expiry_date && Carbon::now()->addDays($days)->greaterThanOrEqualTo($this->expiry_date);
    }

    public function transferToKitchen($quantity, $transferredBy, $notes = null)
    {
        if ($this->status !== 'available') {
            throw new \Exception('Stock is not available for transfer');
        }

        if ($quantity > $this->quantity) {
            throw new \Exception('Cannot transfer more than available quantity');
        }

        // Create kitchen inventory record
        $kitchenInventory = KitchenInventory::create([
            'restaurant_id' => $this->restaurant_id,
            'ingredient_id' => $this->ingredient_id,
            'stock_id' => $this->stock_id,
            'quantity_transferred' => $quantity,
            'quantity_remaining' => $quantity,
            'unit' => $this->unit,
            'transfer_date' => Carbon::now()->toDateString(),
            'expiry_date' => $this->expiry_date,
            'transferred_by' => $transferredBy,
            'transfer_notes' => $notes,
        ]);

        // Update stock quantity
        $this->quantity -= $quantity;

        // If all quantity is transferred, mark as transferred
        if ($this->quantity <= 0) {
            $this->status = 'transferred';
            $this->quantity = 0;
        }

        $this->save();

        return $kitchenInventory;
    }

    public function getAvailableQuantityAttribute()
    {
        return $this->status === 'available' ? $this->quantity : 0;
    }

    public function getTotalTransferredAttribute()
    {
        return $this->kitchenInventories()->sum('quantity_transferred');
    }
}
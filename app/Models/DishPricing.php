<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishPricing extends Model
{
    protected $table = 'dish_pricing';
    protected $primaryKey = 'pricing_id';
    
    protected $fillable = [
        'dish_id',
        'price_type',
        'base_price',
        'promotional_price',
        'promo_start_date',
        'promo_end_date',
        'min_profit_margin',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'promotional_price' => 'decimal:2',
        'min_profit_margin' => 'decimal:2',
        'promo_start_date' => 'date',
        'promo_end_date' => 'date',
    ];

    // Relationships
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    // Helper methods
    public function isPromotionActive(): bool
    {
        if (!$this->promotional_price || !$this->promo_start_date || !$this->promo_end_date) {
            return false;
        }
        
        return now()->between($this->promo_start_date, $this->promo_end_date);
    }

    public function getCurrentPrice(): float
    {
        return $this->isPromotionActive() ? $this->promotional_price : $this->base_price;
    }
}

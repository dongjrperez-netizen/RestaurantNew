<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    protected $table = 'dishes';
    protected $primaryKey = 'dish_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'dish_name',
        'description',
        'preparation_time',
        'serving_size',
        'serving_unit',
        'image_url',
        'calories',
        'allergens',
        'dietary_tags',
        'status',
        'created_by',
    ];

    protected $casts = [
        'serving_size' => 'decimal:2',
        'allergens' => 'json',
        'dietary_tags' => 'json',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id', 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredients::class, 'dish_ingredients', 'dish_id', 'ingredient_id')
            ->withPivot(['quantity', 'unit', 'is_optional', 'preparation_note'])
            ->withTimestamps();
    }

    public function pricing()
    {
        return $this->hasMany(DishPricing::class, 'dish_id', 'dish_id');
    }

    public function costs()
    {
        return $this->hasMany(DishCost::class, 'dish_id', 'dish_id');
    }

    public function schedules()
    {
        return $this->hasMany(MenuSchedule::class, 'dish_id', 'dish_id');
    }

    public function analytics()
    {
        return $this->hasMany(DishSalesAnalytics::class, 'dish_id', 'dish_id');
    }

    public function dishIngredients()
    {
        return $this->hasMany(DishIngredient::class, 'dish_id', 'dish_id');
    }

    public function calculateIngredientQuantities($dishQuantity = 1)
    {
        return $this->dishIngredients->mapWithKeys(function ($dishIngredient) use ($dishQuantity) {
            return [
                $dishIngredient->ingredient_id => $dishIngredient->quantity_needed * $dishQuantity
            ];
        });
    }

    public function hasAvailableStock($dishQuantity = 1)
    {
        foreach ($this->dishIngredients as $dishIngredient) {
            $requiredQuantity = $dishIngredient->quantity_needed * $dishQuantity;
            if ($dishIngredient->ingredient->current_stock < $requiredQuantity) {
                return false;
            }
        }
        return true;
    }

    // New Menu Management Methods
    public function getCurrentPrice($priceType = 'dine_in')
    {
        $pricing = $this->pricing()->where('price_type', $priceType)->first();
        
        if (!$pricing) return null;
        
        // Check if promotional price is active
        if ($pricing->promotional_price && 
            $pricing->promo_start_date && 
            $pricing->promo_end_date &&
            now()->between($pricing->promo_start_date, $pricing->promo_end_date)) {
            return $pricing->promotional_price;
        }
        
        return $pricing->base_price;
    }

    public function getLatestCost()
    {
        return $this->costs()->latest('calculated_at')->first();
    }

    public function calculateCurrentCost()
    {
        $totalCost = 0;
        
        foreach ($this->ingredients as $ingredient) {
            if ($ingredient->suppliers->isNotEmpty()) {
                $firstSupplier = $ingredient->suppliers->first();
                if ($firstSupplier->pivot && $firstSupplier->pivot->package_price && $firstSupplier->pivot->package_quantity) {
                    $costPerUnit = $firstSupplier->pivot->package_price / $firstSupplier->pivot->package_quantity;
                    $totalCost += $costPerUnit * $ingredient->pivot->quantity;
                }
            }
        }
        
        return $totalCost;
    }

    public function isAvailable()
    {
        return $this->status === 'active' && $this->hasAvailableStock();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}

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
        'dish_name',
        'description',
        'selling_price',
        'category',
        'is_available',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredients::class, 'dish_ingredients', 'dish_id', 'ingredient_id')
            ->withPivot(['quantity_needed', 'unit_of_measure'])
            ->withTimestamps();
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
}

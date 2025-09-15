<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DishIngredient extends Model
{
    protected $table = 'dish_ingredients';

    protected $fillable = [
        'dish_id',
        'ingredient_id',
        'quantity_needed',
        'unit_of_measure',
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:4',
    ];

    public function dish()
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }
}

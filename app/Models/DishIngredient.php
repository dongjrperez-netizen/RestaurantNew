<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DishIngredient extends Model
{
    protected $table = 'dish_ingredients';

    protected $fillable = [
        'dish_id',
        'ingredient_id',
        'ingredient_name',
        'quantity',
        'unit',
        'cost_per_unit',
        'is_optional',
        'preparation_note',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'is_optional' => 'boolean',
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

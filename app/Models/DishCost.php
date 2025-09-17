<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DishCost extends Model
{
    protected $primaryKey = 'cost_id';
    
    protected $fillable = [
        'dish_id',
        'ingredient_cost',
        'labor_cost',
        'overhead_cost',
        'total_cost',
        'calculated_at',
    ];

    protected $casts = [
        'ingredient_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2', 
        'overhead_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    // Relationships
    public function dish()
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }
}

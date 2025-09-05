<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredients extends Model
{
    protected $table = 'ingredients';      
    protected $primaryKey = 'ingredient_id'; 
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'ingredient_name',
        'base_unit',
        'current_stock',
        'reorder_level',
    ];

    public function orderItems()
    {
        return $this->hasMany(Restaurant_Order_Items::class, 'ingredient_id', 'ingredient_id');
    }
}

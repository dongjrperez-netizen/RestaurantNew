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

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'ingredient_suppliers', 'ingredient_id', 'supplier_id')
            ->withPivot(['package_unit', 'package_quantity', 'package_price', 'lead_time_days', 'minimum_order_quantity', 'is_active'])
            ->withTimestamps();
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'ingredient_id', 'ingredient_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }
}

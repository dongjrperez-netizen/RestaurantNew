<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';
    protected $primaryKey = 'purchase_order_item_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'purchase_order_id',
        'ingredient_id',
        'ordered_quantity',
        'received_quantity',
        'unit_price',
        'total_price',
        'unit_of_measure',
        'notes'
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }
}

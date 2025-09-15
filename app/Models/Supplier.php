<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Supplier extends Authenticatable
{
    use Notifiable;

    protected $table = 'suppliers';

    protected $primaryKey = 'supplier_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'supplier_name',
        'contact_number',
        'email',
        'password',
        'address',
        'business_registration',
        'tax_id',
        'payment_terms',
        'credit_limit',
        'notes',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function restaurant()
    {
        return $this->belongsTo(\App\Models\Restaurant_Data::class, 'restaurant_id', 'id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredients::class, 'ingredient_suppliers', 'supplier_id', 'ingredient_id')
            ->withPivot(['package_unit', 'package_quantity', 'package_price', 'lead_time_days', 'minimum_order_quantity', 'is_active'])
            ->withTimestamps();
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'supplier_id');
    }

    public function bills()
    {
        return $this->hasMany(SupplierBill::class, 'supplier_id', 'supplier_id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_id', 'supplier_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant_Data extends Model
{
     protected $table = 'restaurant_data'; 
     protected $fillable = [
        'user_id',
        'restaurant_name',
        'address',
    
        'contact_number',
    ];

    // Define the relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

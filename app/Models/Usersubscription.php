<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $table = 'usersubscriptions'; // 👈 fix table name
    protected $primaryKey = 'userSubscription_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'subscription_startDate',
        'subscription_endDate',
        'remaining_days',
        'subscription_status',
        'plan_id',
        'restaurant_id',
    ];

}

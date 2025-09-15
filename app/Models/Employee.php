<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'lastname',
        'firstname',
        'middlename',
        'email',
        'password',
        'date_of_birth',
        'gender',
        'role_id',
        'user_id',
        'status',
    ];

    protected $appends = [
        'full_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getFullNameAttribute()
    {
        $name = $this->firstname;
        if ($this->middlename) {
            $name .= ' '.$this->middlename;
        }
        $name .= ' '.$this->lastname;

        return trim($name);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeForRestaurant($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

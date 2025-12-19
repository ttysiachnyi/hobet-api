<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['user_id', 'streak', 'bonus', 'status', 'last_claimed_at'];

    protected $casts = [
        'last_claimed_at' => 'datetime',
    ];
}

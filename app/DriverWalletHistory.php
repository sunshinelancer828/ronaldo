<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverWalletHistory extends Model
{
    protected $fillable = [
        'id', 'driver_id', 'type','message','amount','created_at','updated_at'
    ];
}

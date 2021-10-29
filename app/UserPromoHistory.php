<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPromoHistory extends Model
{
     protected $fillable = [
        'id','user_id','promo_id','status','created_at','updated-at',
    ];
}

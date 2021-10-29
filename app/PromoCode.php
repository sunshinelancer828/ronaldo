<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
     protected $fillable = [
        'id','country_id','customer_id','promo_name','promo_code','description','promo_type','discount','redemptions','status','created_at','updated_at'
    ];
}

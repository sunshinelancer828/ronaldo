<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'id', 'country_id', 'first_name', 'last_name', 'phone_number','email','password','profile_picture','status', 'country_code','currency_short_code', 'phone_with_code', 'fcm_token', 'currency', 'wallet','gender'
    ];
}

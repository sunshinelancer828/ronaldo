<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverQuery extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'first_name','last_name','phone_number','email', 'description','status'
    ];

}

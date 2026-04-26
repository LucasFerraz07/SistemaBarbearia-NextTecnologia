<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['city_id', 'street', 'number', 'neighborhood', 'cep'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }
}

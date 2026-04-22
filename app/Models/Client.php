<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['user_id', 'phone', 'address', 'city'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedulings()
    {
        return $this->hasMany(Scheduling::class);
    }
}

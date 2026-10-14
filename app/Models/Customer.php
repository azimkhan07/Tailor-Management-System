<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullname',
        'address',
        'phone',
        'city',
        'email',
        'comment',
        'gender',
    ];

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class)->latest();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Orders::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'cloth_type_id',
        'title',
        'details',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function clothType(): BelongsTo
    {
        return $this->belongsTo(ClothType::class);
    }

    public function getShortDetailsAttribute(): string
    {
        $details = trim(preg_replace('/\s+/', ' ', (string) $this->details));

        return mb_strlen($details) > 90 ? mb_substr($details, 0, 90) . '…' : $details;
    }
}

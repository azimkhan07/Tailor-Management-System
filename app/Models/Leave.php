<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'decided_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getDayCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }
}

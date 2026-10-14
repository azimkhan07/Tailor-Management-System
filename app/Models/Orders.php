<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Orders extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['pending', 'in_progress', 'completed', 'delivered', 'cancelled'];

    protected $fillable = [
        'customer_id',
        'order_no',
        'description',
        'status',
        'assigned_to',
        'recieved_on',
        'recieved_by',
        'amount_charged',
        'amount_paid',
        'collecting_on',
        'completed_at',
        'status_updated_at',
    ];

    protected $casts = [
        'recieved_on' => 'date:Y-m-d',
        'collecting_on' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Orders $order) {
            if (blank($order->order_no)) {
                $order->order_no = static::generateOrderNo();
            }

            if (blank($order->status)) {
                $order->status = 'pending';
            }

            $order->status_updated_at = $order->status_updated_at ?: now();
        });

        static::created(function (Orders $order) {
            $order->history()->create([
                'from_status' => null,
                'status' => $order->status,
                'user_id' => auth()->id(),
                'notes' => 'Order created',
            ]);
        });
    }

    /**
     * Build a human-friendly, unique order number:
     * {BRAND3}-{DDMM}-{HHMMSS}-{YYYY}  e.g. TAI-1410-153045-2026
     */
    public static function generateOrderNo(): string
    {
        $brand = strtoupper(substr(
            preg_replace('/[^A-Za-z0-9]/', '', (string) setting('app_name', config('app.name', 'TAIL'))),
            0,
            3
        ));

        if (strlen($brand) < 3) {
            $brand = str_pad($brand, 3, 'X');
        }

        $now = now();
        $base = sprintf(
            '%s-%s-%s-%s',
            $brand,
            $now->format('dm'),
            $now->format('His'),
            $now->format('Y')
        );

        $no = $base;
        $counter = 1;
        while (static::withTrashed()->where('order_no', $no)->exists()) {
            $no = $base . '-' . (++$counter);
        }

        return $no;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')->latest();
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->amount_charged - (float) $this->amount_paid;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return [
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'delivered' => 'primary',
            'cancelled' => 'danger',
        ][$this->status] ?? 'secondary';
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'delivered'], true);
    }
}

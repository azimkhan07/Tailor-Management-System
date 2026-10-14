<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'date',
        'weekday',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'weekday' => 'integer',
    ];

    public function isOneOff(): bool
    {
        return $this->date !== null;
    }

    public function isRecurring(): bool
    {
        return $this->weekday !== null;
    }

    // weekday uses PHP/JS convention: 0 = Sunday … 6 = Saturday.
    public function matches(string $day): bool
    {
        $carbon = \Illuminate\Support\Carbon::parse($day);

        if ($this->date !== null) {
            return $this->date->isSameDay($carbon);
        }

        return $this->weekday !== null
            && (int) $this->weekday === ((int) $carbon->dayOfWeekIso % 7);
    }

    public function getLabelAttribute(): string
    {
        if ($this->date !== null) {
            return $this->title . ' (' . $this->date->format('d M Y') . ')';
        }

        $referenceSunday = \Illuminate\Support\Carbon::createFromDate(2026, 1, 4);

        return $this->title . ' (every ' . $referenceSunday->copy()
            ->addDays((int) $this->weekday)
            ->format('l') . ')';
    }
}

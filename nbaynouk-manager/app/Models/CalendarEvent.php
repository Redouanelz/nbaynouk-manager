<?php

namespace App\Models;

use App\Enums\CalendarEventColor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'event_date', 'color', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'color' => CalendarEventColor::class];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

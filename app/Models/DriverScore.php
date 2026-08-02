<?php

namespace App\Models;

use App\Enums\DriverScoreLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Weekly driver score snapshot: rides, punctuality, ratings, pothole reports,
 * green points → 0-100 score with a level band. The Control Tower scoreboard.
 */
class DriverScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'rides_completed',
        'on_time_rate',
        'rating_avg',
        'pothole_reports',
        'green_points_earned',
        'score',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'rides_completed' => 'integer',
            'on_time_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'pothole_reports' => 'integer',
            'green_points_earned' => 'integer',
            'score' => 'integer',
            'level' => DriverScoreLevel::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

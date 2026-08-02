<?php

namespace App\Models;

use App\Enums\MissionSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Photo + location evidence for a proof-verified mission. The app holds the
 * reward until the promoter/admin approves.
 */
class MissionSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mission_id',
        'proof_photo_path',
        'note',
        'lat',
        'lng',
        'status',
        'reward_awarded',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MissionSubmissionStatus::class,
            'reward_awarded' => 'boolean',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

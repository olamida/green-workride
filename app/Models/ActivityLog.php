<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenience recorder used across services for the change-control trail.
     *
     * @param  string|null  $modelType  model class for the audited record
     */
    public static function log(User $user, string $action, ?string $modelType = null, ?int $modelId = null, array $changes = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $changes,
        ]);
    }
}

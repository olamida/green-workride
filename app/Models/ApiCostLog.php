<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per paid external API call (Google Directions, Mapbox, IdentityPass,
 * Smile, …). Open-source providers (OSRM self-hosted) log rows with cost 0 so
 * we can prove we prefer free infra — the "paid only as capped, logged
 * fallback" rule. KYC calls add user_id/purpose/reference (Sprint 3.6) so the
 * Control Tower can audit who and what cost what, idempotently.
 */
class ApiCostLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'service',
        'cost_naira',
        'meta',
        'user_id',
        'purpose',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'cost_naira' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

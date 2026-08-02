<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Origin-Destination matrix snapshot: who travels from where to where.
 */
class OdMatrix extends Model
{
    use HasFactory;

    protected $table = 'od_matrix';

    protected $fillable = [
        'origin_area',
        'destination_area',
        'count',
        'corridor',
        'period_start',
        'period_end',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

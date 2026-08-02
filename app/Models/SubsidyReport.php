<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Monthly MDA subsidy report snapshot — the auditable "palliative went to
 * transport" document the government asks for.
 */
class SubsidyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'workplace_id',
        'period_start',
        'period_end',
        'staff_funded',
        'rides_funded',
        'subsidy_issued',
        'subsidy_spent',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'staff_funded' => 'integer',
            'rides_funded' => 'integer',
            'subsidy_issued' => 'decimal:2',
            'subsidy_spent' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

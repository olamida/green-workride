<?php

namespace App\Models;

use App\Enums\EmployerProgramType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A corporate mobility client (MDA or private organisation) whose employees'
 * commutes are paid from a funded employer wallet — guide §2.2 streams #2/#4.
 *
 * Coverage policy (program_type):
 *  - full:      employer pays 100% of each eligible fare
 *  - one_way:   employer pays 100% only in covered_direction (to_work/from_work)
 *  - percent:   employer pays percent_covered% of each eligible fare
 *  - capped:    employer pays the fare up to max_per_trip
 */
class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'rc_number',
        'address',
        'zone',
        'workplace_id',
        'program_type',
        'percent_covered',
        'max_per_trip',
        'max_monthly_per_employee',
        'corridors',
        'covered_direction',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'percent_covered' => 'decimal:2',
            'max_per_trip' => 'decimal:2',
            'max_monthly_per_employee' => 'decimal:2',
            'corridors' => 'array',
            'active' => 'boolean',
            'program_type' => EmployerProgramType::class,
        ];
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(EmployerWallet::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(EmployerMember::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Does this employer's program currently cover rides on the given corridor?
     */
    public function coversCorridor(?string $corridor): bool
    {
        if (! $this->corridors) {
            return true;
        }

        return in_array($corridor, $this->corridors, true);
    }
}

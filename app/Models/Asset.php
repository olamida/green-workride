<?php

namespace App\Models;

use App\Enums\AssetAcquisitionType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fleet asset: a bus, car or OBD2 dongle (guide §11). Asset-light: we lease
 * first, own later. Status gates trip publishing via the FleetService.
 */
class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_type',
        'acquisition_type',
        'vin',
        'plate_number',
        'make',
        'model',
        'year',
        'purchase_cost',
        'lease_monthly',
        'depreciation_rate',
        'mileage',
        'status',
        'assigned_driver_id',
        'corridor',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'asset_type' => AssetType::class,
            'acquisition_type' => AssetAcquisitionType::class,
            'status' => AssetStatus::class,
            'year' => 'integer',
            'purchase_cost' => 'decimal:2',
            'lease_monthly' => 'decimal:2',
            'depreciation_rate' => 'decimal:2',
            'mileage' => 'integer',
        ];
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function faults(): HasMany
    {
        return $this->hasMany(Fault::class);
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(Telemetry::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function isServiceable(): bool
    {
        return $this->status->isServiceable();
    }

    public function openFaultsCount(): int
    {
        return $this->faults()->whereIn('status', ['open', 'in_progress'])->count();
    }

    /**
     * Straight-line resale estimate: purchase_cost minus depreciation*years.
     */
    public function resaleValue(?int $years = null): float
    {
        $years ??= max(0, (int) now()->diffInYears($this->created_at ?? now()));
        $depreciated = (float) $this->purchase_cost * ((float) $this->depreciation_rate / 100) * $years;

        return max(0.0, round((float) $this->purchase_cost - $depreciated, 2));
    }
}

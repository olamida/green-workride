<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtfsStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'stop_id',
        'stop_name',
        'stop_lat',
        'stop_lon',
        'corridor',
        'zone',
    ];

    protected function casts(): array
    {
        return [
            'stop_lat' => 'decimal:7',
            'stop_lon' => 'decimal:7',
        ];
    }
}

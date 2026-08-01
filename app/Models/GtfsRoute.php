<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtfsRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'agency_id',
        'route_short_name',
        'route_long_name',
        'route_type',
        'corridor',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsFeedMeta extends Model
{
    protected $table = 'gtfs_feed_meta';

    protected $fillable = [
        'last_generated_at',
        'stops_count',
        'routes_count',
        'trips_count',
        'file_size',
        'feed_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_generated_at' => 'datetime',
        ];
    }
}

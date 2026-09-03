<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'tool_name',
        'file_size',
        'processing_time_ms',
        'ip_address',
        'user_agent',
        'country_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'file_size' => 'integer',
        'processing_time_ms' => 'integer',
    ];
}

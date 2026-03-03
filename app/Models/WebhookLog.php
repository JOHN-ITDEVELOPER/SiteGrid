<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration',
        'event_type',
        'url',
        'method',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'status',
        'error_message',
        'retry_count',
        'last_retry_at',
        'reference',
    ];

    protected $casts = [
        'last_retry_at' => 'datetime',
    ];
}

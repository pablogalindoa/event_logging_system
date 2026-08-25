<?php

namespace App\Models;

use App\Enums\EventLevel;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const DATE_FORMAT = 'Y-m-d H:i:s.vP';

    protected $dateFormat = self::DATE_FORMAT;

    protected $fillable = [
        'level',
        'message',
        'source',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => EventLevel::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}

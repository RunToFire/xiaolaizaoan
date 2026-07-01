<?php

declare(strict_types=1);

namespace App\Model;

class HeartQuote extends Model
{
    protected ?string $table = 'heart_quotes';

    protected array $fillable = [
        'group_id',
        'content',
        'author',
        'is_active',
        'remark',
    ];

    protected array $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

<?php

declare(strict_types=1);

namespace App\Model;

class MaterialGroup extends Model
{
    protected ?string $table = 'material_groups';

    protected array $fillable = [
        'name',
        'type',
        'sort_order',
        'is_active',
        'remark',
    ];

    protected array $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

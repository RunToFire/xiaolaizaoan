<?php

declare(strict_types=1);

namespace App\Model;

class MaterialImage extends Model
{
    protected ?string $table = 'material_images';

    protected array $fillable = [
        'group_id',
        'title',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'width',
        'height',
        'is_active',
        'remark',
    ];

    protected array $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

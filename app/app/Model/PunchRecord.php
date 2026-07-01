<?php

declare(strict_types=1);

namespace App\Model;

class PunchRecord extends Model
{
    protected ?string $table = 'punch_records';

    protected array $fillable = [
        'account_id',
        'user_id',
        'parent_user_id',
        'material_image_id',
        'heart_quote_id',
        'punched_at',
        'latitude',
        'longitude',
        'location_label',
        'image_path',
        'image_url',
        'wechat_media_id',
    ];

    protected array $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'user_id' => 'integer',
        'parent_user_id' => 'integer',
        'material_image_id' => 'integer',
        'heart_quote_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'punched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

<?php

declare(strict_types=1);

namespace App\Model;

class WechatOfficialAccount extends Model
{
    protected ?string $table = 'wechat_official_accounts';

    protected array $fillable = [
        'name',
        'app_id',
        'app_secret',
        'token',
        'aes_key',
        'original_id',
        'encoding_type',
        'is_active',
        'menu_config',
        'menu_published_at',
        'remark',
    ];

    protected array $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'menu_config' => 'array',
        'menu_published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

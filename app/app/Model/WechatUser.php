<?php

declare(strict_types=1);

namespace App\Model;

class WechatUser extends Model
{
    protected ?string $table = 'wechat_users';

    protected array $fillable = [
        'account_id',
        'openid',
        'parent_user_id',
        'nickname',
        'avatar_url',
        'qr_scene',
        'qr_ticket',
        'qr_url',
        'last_latitude',
        'last_longitude',
        'last_location_label',
        'location_updated_at',
        'subscribed_at',
        'last_active_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'parent_user_id' => 'integer',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'location_updated_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'last_active_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

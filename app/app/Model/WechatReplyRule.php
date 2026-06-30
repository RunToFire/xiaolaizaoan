<?php

declare(strict_types=1);

namespace App\Model;

class WechatReplyRule extends Model
{
    protected ?string $table = 'wechat_reply_rules';

    protected array $fillable = [
        'account_id',
        'name',
        'msg_type',
        'event',
        'keyword',
        'keyword_match',
        'reply_type',
        'reply_content',
        'priority',
        'is_active',
    ];

    protected array $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'reply_content' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

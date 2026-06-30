<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateWechatReplyRulesTable extends Migration
{
    public function up(): void
    {
        Schema::create('wechat_reply_rules', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->string('name', 100);
            $table->string('msg_type', 32)->default('*');
            $table->string('event', 64)->nullable();
            $table->string('keyword', 255)->nullable();
            $table->string('keyword_match', 20)->default('contains');
            $table->string('reply_type', 32)->default('text');
            $table->json('reply_content');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('account_id');
            $table->index(['account_id', 'is_active', 'msg_type', 'event', 'priority'], 'idx_wechat_reply_rules_match');
            $table->foreign('account_id')
                ->references('id')
                ->on('wechat_official_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wechat_reply_rules');
    }
}

<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateWechatOfficialAccountsTable extends Migration
{
    public function up(): void
    {
        Schema::create('wechat_official_accounts', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('app_id', 64)->unique();
            $table->string('app_secret', 128);
            $table->string('token', 128);
            $table->string('aes_key', 128)->nullable();
            $table->string('original_id', 64)->nullable();
            $table->string('encoding_type', 20)->default('plaintext');
            $table->boolean('is_active')->default(true)->index();
            $table->json('menu_config')->nullable();
            $table->timestamp('menu_published_at')->nullable();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wechat_official_accounts');
    }
}

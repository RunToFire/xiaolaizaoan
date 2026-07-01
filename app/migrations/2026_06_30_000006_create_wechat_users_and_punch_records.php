<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateWechatUsersAndPunchRecords extends Migration
{
    public function up(): void
    {
        Schema::create('wechat_users', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index();
            $table->string('openid', 100);
            $table->unsignedBigInteger('parent_user_id')->nullable()->index();
            $table->string('nickname', 120)->nullable();
            $table->string('avatar_url', 255)->nullable();
            $table->string('qr_scene', 64)->nullable()->index();
            $table->string('qr_ticket', 255)->nullable();
            $table->string('qr_url', 512)->nullable();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->string('last_location_label', 255)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'openid'], 'uniq_wechat_users_account_openid');
        });

        Schema::create('punch_records', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('parent_user_id')->nullable()->index();
            $table->unsignedBigInteger('material_image_id')->nullable()->index();
            $table->unsignedBigInteger('heart_quote_id')->nullable()->index();
            $table->timestamp('punched_at')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_label', 255)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('image_url', 255)->nullable();
            $table->string('wechat_media_id', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punch_records');
        Schema::dropIfExists('wechat_users');
    }
}

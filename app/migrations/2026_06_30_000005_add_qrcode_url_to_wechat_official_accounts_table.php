<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddQrcodeUrlToWechatOfficialAccountsTable extends Migration
{
    public function up(): void
    {
        Schema::table('wechat_official_accounts', static function (Blueprint $table): void {
            $table->string('qrcode_url', 255)->nullable()->after('original_id');
        });
    }

    public function down(): void
    {
        Schema::table('wechat_official_accounts', static function (Blueprint $table): void {
            $table->dropColumn('qrcode_url');
        });
    }
}

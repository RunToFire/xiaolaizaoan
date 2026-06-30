<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddMenuColumnsToWechatOfficialAccountsTable extends Migration
{
    public function up(): void
    {
        Schema::table('wechat_official_accounts', static function (Blueprint $table): void {
            if (! Schema::hasColumn('wechat_official_accounts', 'menu_config')) {
                $table->json('menu_config')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('wechat_official_accounts', 'menu_published_at')) {
                $table->timestamp('menu_published_at')->nullable()->after('menu_config');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wechat_official_accounts', static function (Blueprint $table): void {
            if (Schema::hasColumn('wechat_official_accounts', 'menu_published_at')) {
                $table->dropColumn('menu_published_at');
            }

            if (Schema::hasColumn('wechat_official_accounts', 'menu_config')) {
                $table->dropColumn('menu_config');
            }
        });
    }
}

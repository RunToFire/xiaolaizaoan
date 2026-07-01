<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMaterialTables extends Migration
{
    public function up(): void
    {
        Schema::create('material_groups', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('type', 20)->default('image')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('material_images', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->string('title', 120)->nullable();
            $table->string('file_path', 255);
            $table->string('file_url', 255);
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('file_size')->default(0);
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('heart_quotes', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->text('content');
            $table->string('author', 80)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heart_quotes');
        Schema::dropIfExists('material_images');
        Schema::dropIfExists('material_groups');
    }
}

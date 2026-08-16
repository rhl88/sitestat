<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_cmspro_sitestat_spider_visits')) {
            return;
        }

        Schema::create('app_cmspro_sitestat_spider_visits', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->string('spider_name', 50)->default('')->comment('蜘蛛名称');
            $table->string('ip', 45)->default('')->comment('蜘蛛IP地址');
            $table->string('url', 500)->default('')->comment('访问页面URL');
            $table->string('path', 255)->default('')->comment('页面路径');
            $table->string('user_agent', 500)->default('')->comment('UA字符串');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：0-禁用 1-启用');
            $table->timestamp('create_time')->nullable()->comment('创建时间');
            $table->timestamp('update_time')->nullable()->comment('更新时间');

            $table->index('spider_name');
            $table->index('ip');
            $table->index('path');
            $table->index('create_time');
        });

        DB::statement("ALTER TABLE `app_cmspro_sitestat_spider_visits` COMMENT '访问统计-蜘蛛访问记录表'");
    }

    public function down(): void
    {
        Schema::dropIfExists('app_cmspro_sitestat_spider_visits');
    }
};

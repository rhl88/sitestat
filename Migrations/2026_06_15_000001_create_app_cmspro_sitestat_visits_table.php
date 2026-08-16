<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_cmspro_sitestat_visits')) {
            return;
        }

        Schema::create('app_cmspro_sitestat_visits', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->string('session_id', 64)->default('')->comment('会话ID，用于UV去重');
            $table->string('ip', 45)->default('')->comment('访客IP地址');
            $table->string('country', 50)->default('')->comment('国家');
            $table->string('province', 50)->default('')->comment('省份');
            $table->string('city', 50)->default('')->comment('城市');
            $table->string('isp', 50)->default('')->comment('运营商');
            $table->string('url', 500)->default('')->comment('访问页面URL');
            $table->string('path', 255)->default('')->comment('页面路径');
            $table->string('title', 255)->default('')->comment('页面标题');
            $table->string('referer', 500)->default('')->comment('来源URL');
            $table->string('referer_domain', 255)->default('')->comment('来源域名');
            $table->unsignedTinyInteger('referer_type')->default(1)->comment('来源类型：1-直接访问 2-搜索引擎 3-外部链接');
            $table->string('search_engine', 50)->default('')->comment('搜索引擎名称');
            $table->string('search_keyword', 255)->default('')->comment('搜索关键词');
            $table->string('user_agent', 500)->default('')->comment('UA字符串');
            $table->string('browser', 50)->default('')->comment('浏览器');
            $table->string('os', 50)->default('')->comment('操作系统');
            $table->string('device_type', 20)->default('desktop')->comment('设备类型：desktop/mobile/tablet');
            $table->string('screen_resolution', 20)->default('')->comment('屏幕分辨率');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：0-禁用 1-启用');
            $table->timestamp('create_time')->nullable()->comment('创建时间');
            $table->timestamp('update_time')->nullable()->comment('更新时间');

            $table->index('session_id');
            $table->index('ip');
            $table->index('path');
            $table->index('create_time');
            $table->index('referer_type');
        });

        DB::statement("ALTER TABLE `app_cmspro_sitestat_visits` COMMENT '访问统计-原始访问记录表'");
    }

    public function down(): void
    {
        Schema::dropIfExists('app_cmspro_sitestat_visits');
    }
};

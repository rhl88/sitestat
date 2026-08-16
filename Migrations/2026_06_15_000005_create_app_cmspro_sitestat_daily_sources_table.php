<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_cmspro_sitestat_daily_sources')) {
            return;
        }

        Schema::create('app_cmspro_sitestat_daily_sources', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->date('stat_date')->comment('统计日期');
            $table->unsignedTinyInteger('referer_type')->default(1)->comment('来源类型：1-直接访问 2-搜索引擎 3-外部链接');
            $table->string('referer_domain', 255)->default('')->comment('来源域名');
            $table->string('search_engine', 50)->default('')->comment('搜索引擎名称');
            $table->string('search_keyword', 255)->default('')->comment('搜索关键词');
            $table->unsignedInteger('pv')->default(0)->comment('PV');
            $table->unsignedInteger('uv')->default(0)->comment('UV');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：0-禁用 1-启用');
            $table->timestamp('create_time')->nullable()->comment('创建时间');
            $table->timestamp('update_time')->nullable()->comment('更新时间');

            $table->unique(['stat_date', 'referer_type', 'referer_domain', 'search_engine', 'search_keyword'], 'daily_sources_unique');
        });

        DB::statement("ALTER TABLE `app_cmspro_sitestat_daily_sources` COMMENT '访问统计-来源日统计表'");
    }

    public function down(): void
    {
        Schema::dropIfExists('app_cmspro_sitestat_daily_sources');
    }
};

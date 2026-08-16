<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_cmspro_sitestat_daily_stats')) {
            return;
        }

        Schema::create('app_cmspro_sitestat_daily_stats', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->date('stat_date')->comment('统计日期');
            $table->unsignedInteger('pv')->default(0)->comment('页面浏览量');
            $table->unsignedInteger('uv')->default(0)->comment('独立访客数');
            $table->unsignedInteger('ip_count')->default(0)->comment('独立IP数');
            $table->unsignedInteger('new_uv')->default(0)->comment('新访客数');
            $table->decimal('bounce_rate', 5, 2)->default(0)->comment('跳出率(%)');
            $table->unsignedInteger('avg_duration')->default(0)->comment('平均访问时长(秒)');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：0-禁用 1-启用');
            $table->timestamp('create_time')->nullable()->comment('创建时间');
            $table->timestamp('update_time')->nullable()->comment('更新时间');

            $table->unique('stat_date');
        });

        DB::statement("ALTER TABLE `app_cmspro_sitestat_daily_stats` COMMENT '访问统计-日统计汇总表'");
    }

    public function down(): void
    {
        Schema::dropIfExists('app_cmspro_sitestat_daily_stats');
    }
};

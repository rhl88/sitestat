<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_cmspro_sitestat_daily_regions')) {
            return;
        }

        Schema::create('app_cmspro_sitestat_daily_regions', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->date('stat_date')->comment('统计日期');
            $table->string('country', 50)->default('')->comment('国家');
            $table->string('province', 50)->default('')->comment('省份');
            $table->string('city', 50)->default('')->comment('城市');
            $table->unsignedInteger('pv')->default(0)->comment('PV');
            $table->unsignedInteger('uv')->default(0)->comment('UV');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：0-禁用 1-启用');
            $table->timestamp('create_time')->nullable()->comment('创建时间');
            $table->timestamp('update_time')->nullable()->comment('更新时间');

            $table->unique(['stat_date', 'province', 'city']);
        });

        DB::statement("ALTER TABLE `app_cmspro_sitestat_daily_regions` COMMENT '访问统计-地域日统计表'");
    }

    public function down(): void
    {
        Schema::dropIfExists('app_cmspro_sitestat_daily_regions');
    }
};

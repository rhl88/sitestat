<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_cmspro_sitestat_daily_devices')) {
            return;
        }

        Schema::create('app_cmspro_sitestat_daily_devices', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->date('stat_date')->comment('统计日期');
            $table->string('browser', 50)->default('')->comment('浏览器');
            $table->string('os', 50)->default('')->comment('操作系统');
            $table->string('device_type', 20)->default('desktop')->comment('设备类型：desktop/mobile/tablet');
            $table->string('screen_resolution', 20)->default('')->comment('屏幕分辨率');
            $table->unsignedInteger('pv')->default(0)->comment('PV');
            $table->unsignedInteger('uv')->default(0)->comment('UV');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：0-禁用 1-启用');
            $table->timestamp('create_time')->nullable()->comment('创建时间');
            $table->timestamp('update_time')->nullable()->comment('更新时间');

            $table->unique(['stat_date', 'browser', 'os', 'device_type', 'screen_resolution'], 'daily_devices_unique');
        });

        DB::statement("ALTER TABLE `app_cmspro_sitestat_daily_devices` COMMENT '访问统计-设备日统计表'");
    }

    public function down(): void
    {
        Schema::dropIfExists('app_cmspro_sitestat_daily_devices');
    }
};

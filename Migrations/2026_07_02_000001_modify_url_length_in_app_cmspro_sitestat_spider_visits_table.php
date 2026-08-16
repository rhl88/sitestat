<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 将 url 列从 varchar(500) 扩展为 varchar(2000)
        DB::statement("ALTER TABLE `app_cmspro_sitestat_spider_visits` MODIFY COLUMN `url` varchar(2000) NOT NULL DEFAULT '' COMMENT '访问页面URL'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `app_cmspro_sitestat_spider_visits` MODIFY COLUMN `url` varchar(500) NOT NULL DEFAULT '' COMMENT '访问页面URL'");
    }
};

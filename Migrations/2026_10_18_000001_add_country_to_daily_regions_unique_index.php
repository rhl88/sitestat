<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 地域日统计表唯一索引加入国家维度
 *
 * 原唯一索引为 (stat_date, province, city)，不含国家；当多个国家的 IP 均只能解析到
 * 国家级别（省/市为空）时，聚合写入会互相覆盖，导致国家信息错误、PV 丢失。
 * 本迁移将唯一索引调整为 (stat_date, country, province, city)。
 */
return new class extends Migration
{
    /** 目标表名 */
    private const TABLE = 'app_cmspro_sitestat_daily_regions';

    /** 旧唯一索引列组合（不含国家） */
    private const LEGACY_COLUMNS = ['stat_date', 'province', 'city'];

    /** 新唯一索引列组合（含国家） */
    private const UNIQUE_COLUMNS = ['stat_date', 'country', 'province', 'city'];

    /** 新唯一索引名（表名较长，显式命名以避免超出 MySQL 64 字符索引名限制） */
    private const UNIQUE_NAME = 'uk_sitestat_region_date_country_prov_city';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        // 删除旧唯一索引：旧索引名由框架按表名+列名自动生成，这里按列组合动态匹配
        foreach (Schema::getIndexes(self::TABLE) as $index) {
            if (!empty($index['unique']) && $index['columns'] === self::LEGACY_COLUMNS) {
                Schema::table(self::TABLE, function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            }
        }

        // 建立包含国家的唯一索引
        if (!Schema::hasIndex(self::TABLE, self::UNIQUE_COLUMNS, 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique(self::UNIQUE_COLUMNS, self::UNIQUE_NAME);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        if (Schema::hasIndex(self::TABLE, self::UNIQUE_NAME)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_NAME);
            });
        }

        // 还原旧唯一索引（列组合可能与新索引相同，需先确认不存在）
        if (!Schema::hasIndex(self::TABLE, self::LEGACY_COLUMNS, 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique(self::LEGACY_COLUMNS);
            });
        }
    }
};
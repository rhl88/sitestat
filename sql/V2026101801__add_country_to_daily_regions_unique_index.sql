-- ============================================================================
-- 数据库变更脚本
-- ============================================================================
-- 版本号: V2026101801
-- 变更描述: 为 app_cmspro_sitestat_daily_regions 表唯一索引加入国家维度
--           原唯一索引 (stat_date, province, city) 不含国家，当多个国家的 IP 均
--           只能解析到国家级别（省/市为空字符串）时，日聚合写入会互相覆盖，
--           造成国家信息错误、PV/UV 数据丢失。
--           本脚本将唯一索引调整为 (stat_date, country, province, city)。
-- 影响表: app_cmspro_sitestat_daily_regions
-- 变更类型: DDL (索引结构变更)
-- 作者: AI Assistant
-- 创建日期: 2026-10-18
-- 关联需求/Issue: CmsproSitestat 地域统计增加国家维度，修复跨国家聚合覆盖
-- 预计执行时间: < 1秒（表数据量越大耗时越长，索引重建期间该表写入会短暂阻塞）
-- 风险等级: 低
-- 说明: 本脚本为幂等脚本，可重复执行；与应用迁移
--       Migrations/2026_10_18_000001_add_country_to_daily_regions_unique_index.php 等价，
--       二者执行其一即可（推荐通过应用升级自动执行迁移，此脚本供人工/DBA 场景使用）。
-- ============================================================================

-- ---------- 正向变更 ----------

-- 1. 删除旧唯一索引 (stat_date, province, city)
--    旧索引名由框架按“表名_列名”自动生成，这里通过 information_schema 按列组合动态定位，
--    兼容索引名被截断或人工改名的情况
SET @old_index := (
    SELECT INDEX_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'app_cmspro_sitestat_daily_regions'
      AND NON_UNIQUE = 0
    GROUP BY INDEX_NAME
    HAVING GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) = 'stat_date,province,city'
    LIMIT 1
);

SET @sql := IF(@old_index IS NOT NULL,
    CONCAT('ALTER TABLE `app_cmspro_sitestat_daily_regions` DROP INDEX `', @old_index, '`'),
    'SELECT ''旧唯一索引不存在，跳过删除'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 建立包含国家维度的新唯一索引 (stat_date, country, province, city)
--    表名与列名较长，若不显式命名会超出 MySQL 64 字符索引名限制，故显式命名为 uk_sitestat_region_date_country_prov_city
SET @has_new_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'app_cmspro_sitestat_daily_regions'
      AND INDEX_NAME = 'uk_sitestat_region_date_country_prov_city'
);

SET @sql := IF(@has_new_index = 0,
    'ALTER TABLE `app_cmspro_sitestat_daily_regions` ADD UNIQUE KEY `uk_sitestat_region_date_country_prov_city` (`stat_date`, `country`, `province`, `city`)',
    'SELECT ''新唯一索引已存在，跳过创建'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------- 数据验证 ----------
-- 验证唯一索引已调整为目标列组合（应看到 uk_sitestat_region_date_country_prov_city 一行，
-- 且不再存在列组合为 stat_date,province,city 的唯一索引）
SELECT INDEX_NAME,
       GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS index_columns,
       IF(NON_UNIQUE = 0, 'UNIQUE', 'NORMAL') AS index_type
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'app_cmspro_sitestat_daily_regions'
GROUP BY INDEX_NAME, NON_UNIQUE
ORDER BY INDEX_NAME;

-- ---------- 可选：历史聚合数据重算 ----------
-- 唯一索引调整仅影响后续写入，不会自动修正历史聚合结果。
-- 若历史数据中已存在跨国家覆盖（某日某省/市记录错归到其它国家），可按以下方式重算指定日期的地域聚合：
-- 1) 删除指定日期的地域聚合数据：
-- DELETE FROM `app_cmspro_sitestat_daily_regions` WHERE `stat_date` = 'YYYY-MM-DD';
-- 2) 通过应用重跑当日聚合（二选一）：
--    a. 若应用提供聚合命令（如 php artisan sitestat:aggregate --date=YYYY-MM-DD，以实际命令为准）；
--    b. 或调用 DailyAggregationService::aggregate('YYYY-MM-DD') 触发重算。
-- 说明：重算会读取该日期的原始访问记录（visits 表）重新生成，请确认原始数据仍在保留期内。

-- ---------- 回滚方案 ----------
-- 如需回滚，执行以下SQL（先删除新索引，再恢复旧索引）:
-- 1) 删除含国家维度的新唯一索引：
-- DROP INDEX `uk_sitestat_region_date_country_prov_city` ON `app_cmspro_sitestat_daily_regions`;
-- 2) 恢复旧唯一索引 (stat_date, province, city)：
-- ALTER TABLE `app_cmspro_sitestat_daily_regions`
--     ADD UNIQUE KEY `app_cmspro_sitestat_daily_regions_stat_date_province_city_unique` (`stat_date`, `province`, `city`);
-- 注意：回滚前需确认不存在“同 stat_date 同 province 同 city 但不同 country”的多行数据，
--       否则恢复旧索引会因唯一约束冲突而失败；如存在此类数据需先合并或清理。
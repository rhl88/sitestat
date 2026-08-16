-- ============================================================================
-- 数据库变更脚本
-- ============================================================================
-- 版本号: V2026070201
-- 变更描述: 扩展 app_cmspro_sitestat_spider_visits 表 url 字段长度
--           从 VARCHAR(500) 改为 VARCHAR(2000)，防止超长URL写入失败
-- 影响表: app_cmspro_sitestat_spider_visits
-- 变更类型: DDL (结构变更)
-- 作者: AI Assistant
-- 创建日期: 2026-07-02
-- 关联需求/Issue: 蜘蛛访问记录写入失败，URL字段数据超长
-- 预计执行时间: < 1秒
-- 风险等级: 低
-- ============================================================================

-- ---------- 正向变更 ----------
-- 将 url 列从 varchar(500) 扩展为 varchar(2000)
ALTER TABLE `app_cmspro_sitestat_spider_visits`
    MODIFY COLUMN `url` varchar(2000) NOT NULL DEFAULT '' COMMENT '访问页面URL';

-- ---------- 数据验证 ----------
-- 验证字段修改是否成功
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'app_cmspro_sitestat_spider_visits'
  AND COLUMN_NAME = 'url';

-- ---------- 回滚方案 ----------
-- 如需回滚，执行以下SQL:
-- ALTER TABLE `app_cmspro_sitestat_spider_visits`
--     MODIFY COLUMN `url` varchar(500) NOT NULL DEFAULT '' COMMENT '访问页面URL';

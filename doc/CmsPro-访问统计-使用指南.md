# CmsPro-访问统计-使用指南

> 版本：1.0.0 | 更新日期：2026-08-13
> 应用版本：1.0.1 | 应用 ID：cmspro.sitestat

---

## 一、环境要求

| 项目 | 要求 |
|------|------|
| CmsPro 版本 | v5.0.0 及以上 |
| PHP 版本 | >= 8.3 |
| 数据库 | MySQL（支持 `datetime`、`text` 等常规类型） |
| 依赖应用 | `cmspro.iplocation`（IP 归属地应用，必须安装并启用） |
| 调度器 | 已配置 Laravel 调度器（crontab 每分钟执行 `php artisan schedule:run`），否则日聚合与数据清理任务不会自动执行 |

> 依赖声明见 `manifest.json` 的 `dependencies` 字段，安装时系统会自动校验 `cmspro.iplocation` 应用是否已安装启用。若未配置 IP 库，地域统计字段将为空，但不影响其余统计功能。

---

## 二、安装

### 2.1 安装步骤

1. 通过 CmsPro 后台「应用市场」安装本应用，系统会自动校验 `cmspro.iplocation` 依赖。
2. 安装完成后，`Install.php` 的 `install()` 方法会自动执行 `Migrations/` 目录下所有迁移，创建 `app_cmspro_sitestat_*` 系列数据表。
3. 确认后台「访问统计」菜单已出现，且 8 个子菜单（访问概览、访客地域、来源分析、页面统计、设备分析、访客明细、蜘蛛统计、统计设置）均可访问。
4. 在「统计设置」页配置统计模式等参数，并获取 JS SDK 嵌入代码。

### 2.2 升级

升级时 `Install.php` 的 `upgrade()` 方法会再次执行 `Migrations/` 目录下尚未执行的迁移（如 `2026_07_02_000001_modify_url_length_in_app_cmspro_sitestat_spider_visits_table.php`，用于将蜘蛛访问记录的 `url` 字段长度调整为 2000 字符）。对应 SQL 升级脚本见 `sql/V2026070201__alter_spider_visits_url_length.sql`。

### 2.3 卸载

卸载时 `Install.php` 的 `uninstall()` 方法会按逆序回滚 `Migrations/` 目录下所有迁移，删除全部 `app_cmspro_sitestat_*` 数据表。卸载前请确认不再需要历史统计数据。

---

## 三、功能验证

### 3.1 后台验证

| 菜单 | 路径 | 验证要点 |
|------|------|----------|
| 访问概览 | `/admin/cmspro/sitestat/dashboard` | 展示总 PV/UV/IP 数、今日/昨日数据、平均跳出率、PV/UV 趋势图 |
| 访客地域 | `/admin/cmspro/sitestat/region` | 按省份/城市统计访客分布（PV/UV） |
| 来源分析 | `/admin/cmspro/sitestat/source` | 来源类型占比、搜索引擎排行、外部链接排行、搜索关键词排行 |
| 页面统计 | `/admin/cmspro/sitestat/page` | 页面热度排行（按路径/标题统计 PV/UV） |
| 设备分析 | `/admin/cmspro/sitestat/device` | 浏览器、操作系统、设备类型、屏幕分辨率分布 |
| 访客明细 | `/admin/cmspro/sitestat/visitor` | 原始访问记录明细列表 |
| 蜘蛛统计 | `/admin/cmspro/sitestat/spider` | 蜘蛛访问总览、蜘蛛类型分布、访问趋势、访问明细 |
| 统计设置 | `/admin/cmspro/sitestat/settings` | 统计模式、抽样率、排除路径、数据保留天数、地域统计开关、蜘蛛管理 |

### 3.2 用户端/前端验证（埋点采集）

1. 在「统计设置」页点击获取 JS SDK 地址与嵌入代码（`/apps/cmspro.sitestat/js/sitestat.js`）。
2. 将 `<script src="/apps/cmspro.sitestat/js/sitestat.js"></script>` 插入到需要统计的站点页面 `<head>` 或 `</body>` 前。
3. 页面加载后 SDK 自动上报访问数据，支持 `navigator.sendBeacon`（页面卸载时更可靠），降级为同步 XHR。
4. 验证方式：访问目标页面后，在「访客明细」中应能看到对应的访问记录；在「访问概览」中今日 PV/UV 应随之增长。

### 3.3 API 验证

#### 公开采集接口

- **POST** `/api/sitestat/collect`：公开访问，无需登录，受 `throttle:120,1`（每分钟 120 次）限流保护。返回 1x1 透明 GIF（`image/gif`，`Access-Control-Allow-Origin: *`）。
- 请求参数：`sid`、`url`、`path`、`title`、`referer`、`ua`、`screen`（均为可选）。
- 处理流程：抽样检查 → 路径排除 → IP 归属地解析 → UA 解析 → 来源解析 → 写入 `visits` 表。

#### 后台管理 API（前缀 `/admin/cmspro/sitestat`，需登录后台）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/dashboard` | 访问概览数据 |
| GET | `/api/region` | 地域统计（`group_by` 支持 province/city） |
| GET | `/api/source` | 来源分析 |
| GET | `/api/page` | 页面统计 |
| GET | `/api/device` | 设备分析 |
| GET | `/api/visitor` | 访客明细 |
| GET | `/api/spider` | 蜘蛛统计概览 |
| GET | `/api/spider/visits` | 蜘蛛访问明细 |
| GET | `/api/settings` | 获取统计设置 |
| POST | `/api/settings` | 保存统计设置（含蜘蛛字段时自动重新生成 robots.txt） |
| GET | `/api/js-sdk-url` | 获取 JS SDK 地址与嵌入代码 |

### 3.4 定时任务验证

| 任务名 | 执行时间 | 说明 |
|--------|----------|------|
| `sitestat:daily-aggregate` | 每日 00:10 | 调用 `DailyAggregationService->aggregate()` 聚合昨日原始数据到 5 张日统计表 |
| `sitestat:clean-expired` | 每日 03:00 | 清理超过「原始数据保留天数」的访问记录与蜘蛛访问记录 |

验证方式：确认服务器已配置调度器，次日查看「访问概览」昨日数据是否已聚合到日统计表。

---

## 四、卸载验证

1. 在后台「应用市场」卸载本应用。
2. 确认 `app_cmspro_sitestat_*` 系列数据表已被删除。
3. 确认后台「访问统计」菜单已消失。
4. 确认 `public/robots.txt` 中由本应用生成的蜘蛛规则已不再由本应用维护（卸载不会自动删除 robots.txt，如需清理请手动处理）。

---

## 五、目录结构

```
CmsproSitestat/
├── Assets/
│   └── js/
│       ├── echarts.min.js      # 图表库
│       └── sitestat.js         # 前端统计 SDK
├── Config/
│   └── sitestat.php            # 配置默认值（config key：apps.cmspro.sitestat）
├── Controllers/                # 控制器（8 个后台控制器 + 采集控制器）
├── Middleware/
│   └── SpiderTrackMiddleware.php  # 蜘蛛追踪与防护中间件
├── Migrations/                 # 数据库迁移（7 张表 + 1 个字段调整）
├── Models/                     # 数据模型（7 个）
├── Routes/
│   ├── admin.php               # 后台管理路由
│   └── api.php                 # 公开采集路由
├── Services/                   # 服务层（5 个）
├── Views/                      # Blade 视图（8 个页面）
├── doc/                        # 应用文档
├── sql/
│   └── V2026070201__alter_spider_visits_url_length.sql  # 蜘蛛 URL 长度升级脚本
├── Install.php                 # 安装/卸载/升级脚本
├── ServiceProvider.php         # 服务提供者
├── icon.png                    # 应用图标
└── manifest.json               # 应用清单
```

---

## 六、常见问题

**Q1：报表数据比实际访问少？**
- 检查统计模式是否为抽样模式，抽样率过低会丢弃部分数据。
- 检查访问路径是否命中 `exclude_paths` 排除规则。
- 检查 `cmspro.iplocation` 是否已配置（未配置时采集流程会跳过 IP 解析，但不会丢 PV 数据）。

**Q2：地域统计全部为空？**
- 确认「开启地域统计」开关为开启状态。
- 确认 `cmspro.iplocation` 应用已启用且 CZDB 文件与密钥配置正确。

**Q3：今日数据有，但昨天及之前的数据查不到？**
- 确认服务器已配置调度器，日聚合任务 `sitestat:daily-aggregate` 每日 00:10 执行。
- 聚合任务失败会写日志（关键字「访问统计日聚合失败」）；未聚合的日期报表会从原始表实时补全。

**Q4：蜘蛛没有被记录？**
- 确认「蜘蛛追踪开关」已开启。
- 确认蜘蛛访问路径不在排除范围（后台/API/静态资源路径不记录）。
- 同一蜘蛛同 IP 同路径 5 分钟内重复访问会被去重。

**Q5：修改蜘蛛设置后 robots.txt 没有生效？**
- 保存设置时仅当提交了 `crawl_delay`、`custom_exclude_paths`、`blocked_spiders`、`spider_track_enabled` 任一字段才会重新生成 robots.txt。
- 检查 `public/robots.txt` 目录是否可写（写入失败会记录 warning 日志）。

**Q6：采集接口报 429？**
- `POST /api/sitestat/collect` 受每分钟 120 次限流保护；高流量站点建议改为抽样统计模式，或部署时调大限流阈值。

---

## 七、版本历史

| 版本 | 日期 | 变更内容 |
|------|------|----------|
| 1.0.1 | 2026-08-13 | 当前版本（含蜘蛛 URL 长度调整迁移） |
| 1.0.0 | 2026-08-12 | 初始版本 |

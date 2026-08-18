# CmsPro-访问统计应用文档

> 版本：v1.0.0 | 创建日期：2026-08-12
> 适用系统：CmsPro v5.0.0+
> 应用 ID：`cmspro.sitestat`

---

## 一、应用概述

### 1.1 用途

访问统计（CmsproSitestat）是 CmsPro 自研的网站访问统计分析应用，为站点提供全面的访问数据统计与分析能力，覆盖 PV/UV 统计、IP 地域分布、来源分析、页面热度、设备浏览器统计、蜘蛛爬虫追踪六大维度。

应用通过前端 JavaScript SDK 采集访问数据，服务端解析 IP 归属地与 User-Agent 后入库，并由每日定时任务聚合为按天统计报表。聚合表保证大时间范围查询的性能，原始访问表支持访客明细与实时数据的补全展示。

### 1.2 适用范围

- 需要了解站点流量规模与增长趋势的运营人员
- 需要分析访客地域、来源渠道、页面热度的市场人员
- 需要监控搜索引擎蜘蛛抓取行为、配置 robots.txt 反爬策略的站长/运维

### 1.3 依赖

| 依赖 | 说明 |
|------|------|
| `cmspro.iplocation`（应用） | 必须安装且启用，用于解析访客 IP 归属地（国家/省份/城市/运营商） |

> 依赖声明见 `manifest.json` 的 `dependencies` 字段，安装时系统会自动校验 `cmspro.iplocation` 应用是否已安装启用。若未配置 IP 库，地域统计字段将为空，但不影响其余统计功能。

---

## 二、功能说明

### 2.1 功能模块

基于 `manifest.json` 的 `menus` 结构，应用在后台「访问统计」菜单下提供 8 个子功能模块：

| 菜单 | 路径 | 功能说明 |
|------|------|----------|
| 访问概览 | `/admin/cmspro/sitestat/dashboard` | 总 PV/UV/IP 数、今日/昨日数据、平均跳出率、PV/UV 趋势图 |
| 访客地域 | `/admin/cmspro/sitestat/region` | 按省份/城市统计访客分布（PV/UV） |
| 来源分析 | `/admin/cmspro/sitestat/source` | 来源类型占比、搜索引擎排行、外部链接排行、搜索关键词排行 |
| 页面统计 | `/admin/cmspro/sitestat/page` | 页面热度排行（按路径/标题统计 PV/UV） |
| 设备分析 | `/admin/cmspro/sitestat/device` | 浏览器、操作系统、设备类型、屏幕分辨率分布 |
| 访客明细 | `/admin/cmspro/sitestat/visitor` | 原始访问记录明细列表 |
| 蜘蛛统计 | `/admin/cmspro/sitestat/spider` | 蜘蛛访问总览、蜘蛛类型分布、访问趋势、访问明细 |
| 统计设置 | `/admin/cmspro/sitestat/settings` | 统计模式、抽样率、排除路径、数据保留天数、地域统计开关、蜘蛛管理 |

### 2.2 操作流程

#### 接入统计（前端埋点）

1. 安装并启用本应用
2. 在「统计设置」页点击获取 JS SDK 地址与嵌入代码（`/apps/cmspro.sitestat/js/sitestat.js`）
3. 将 `<script src="/apps/cmspro.sitestat/js/sitestat.js"></script>` 插入到需要统计的站点页面 `<head>` 或 `</body>` 前
4. 页面加载后 SDK 自动上报访问数据（详情见 2.3），支持传统同步 XHR 方式访问 `POST /api/sitestat/collect`（兼容 1x1 GIF 埋点场景）

#### 查看统计报表

在后台「访问统计」各子菜单中，选择时间范围（默认近 30 天）即可查看对应维度的统计图表与排行。

#### 蜘蛛管理（可选项）

在「统计设置」页可配置蜘蛛追踪开关、登录页动作、抓取间隔、屏蔽蜘蛛列表等，保存后自动重新生成 `public/robots.txt`（详见 2.4）。

### 2.3 前端 SDK 采集机制

`Assets/js/sitestat.js` 为自研统计 SDK，特性如下：

- **访客标识（sid）**：通过 Cookie（`cmspro_sitestat_sid`）生成 365 天有效的唯一访客 ID；无 Cookie 时服务端回退为 `md5(ip + ua + 日期)` 作为当日会话 ID
- **上报方式**：优先 `navigator.sendBeacon`（页面卸载时更可靠），降级为同步 XHR
- **SPA 支持**：监听 `popstate`、`pushState`、`replaceState`，路由变化时自动二次上报
- **上报字段**：`sid`、`url`、`path`、`title`、`referer`、`ua`、`screen`

服务端采集接口 `POST /api/sitestat/collect` 返回 1x1 透明 GIF（`image/gif`，`Access-Control-Allow-Origin: *`），兼容跨域与传统统计方式。该接口公开访问并受 `throttle:120,1`（每分钟 120 次）限流保护。

### 2.4 蜘蛛追踪与防护（SpiderTrackMiddleware）

应用将 `SpiderTrackMiddleware` 中间件注册进 `web` 路由组，对所有 Web 请求按七层防护逻辑处理：

| 层级 | 检测项 | 动作 |
|------|--------|------|
| Layer0 | 蜘蛛跟踪开关（`spider_track_enabled`） | 关闭则不处理 |
| Layer1 | URL 中 `redirect=` 参数嵌套 >= 3 次 | 返回 404 迷惑蜘蛛 |
| Layer2 | 登录页动作（`login_page_action`） | `allow` / `block_404` / `block_403` |
| Layer3 | 无 SEO 价值路径（后台/API/静态资源等） | 放行不记录 |
| Layer5 | 蜘蛛黑名单（`blocked_spiders`） | 返回 403 |
| Layer5.1 | IP 黑名单（`blocked_ips`） | 返回 403 |
| Layer5.2 | UA 黑名单（`blocked_uas`，子串匹配） | 返回 403 |
| Layer4 | 频率控制（`isRateLimited`，如 Google 300 次/分） | 返回 429 + `Retry-After: 60` |
| Layer6 | 响应头引导 | 追加 `X-Robots-Tag: noindex, nofollow` 与 `Crawl-Delay` |

同时在「统计设置」中配置蜘蛛相关参数后，会自动生成 `public/robots.txt`（含被屏蔽蜘蛛的专用 `User-agent`/`Disallow`/`Crawl-Delay: 30` 规则、默认 `Disallow` 路径、自定义排除路径与通用 `Crawl-Delay`）。

#### IP / UA 拉黑

在「蜘蛛统计」页的「最近访问记录」表格中，将鼠标悬停到某行的 **IP 列** 或 **UA 列** 会显示「拉黑」按钮（按钮固定在单元格右侧，不受长文本影响），点击即可将该 IP 或 UA 加入黑名单。被拉黑的 IP/UA 的蜘蛛访问将被中间件直接返回 403 拦截。

- IP 黑名单按精确匹配拦截；UA 黑名单按子串匹配拦截（命中 UA 中包含该字符串即拦截）。
- 黑名单数据存储于配置项 `app_cmspro_sitestat_blocked_ips` 与 `app_cmspro_sitestat_blocked_uas`（每行一个）。
- 黑名单读取走缓存（`Cache::remember`，key 为 `cmspro_sitestat_blocked_ips` / `cmspro_sitestat_blocked_uas`，TTL 1 小时），避免每个蜘蛛请求重复查库；任何写入（添加/编辑/删除）均会在 `saveBlockedList` 中自动 `Cache::forget` 对应 key，确保即时生效。
- 「最近访问记录」卡片右上角提供「黑名单管理」按钮，弹窗内支持：
  - **查询**：按 IP/UA 类型切换 + 关键词模糊查询
  - **添加**：手动输入 IP 或 UA 加入黑名单
  - **编辑**：修改黑名单中的 IP/UA 值
  - **删除**：解除黑名单
- 也可在「统计设置」页的「黑名单管理」卡片中查看并解除已拉黑的 IP/UA。

### 2.5 定时任务

| 任务名 | 执行时间 | 说明 |
|--------|----------|------|
| `sitestat:daily-aggregate` | 每日 00:10 | 调用 `DailyAggregationService->aggregate()` 聚合昨日原始数据到 5 张日统计表 |
| `sitestat:clean-expired` | 每日 03:00 | 清理超过「原始数据保留天数」的访问记录与蜘蛛访问记录 |

### 2.6 权限

`manifest.json` 声明 9 项权限：`cmspro.sitestat.manage`（管理）及 dashboard/region/source/page/device/visitor/spider/settings 八项查看权限，对应各子菜单的访问控制。

---

## 三、技术架构

### 3.1 架构总览

```
前端页面（站内嵌入 JS SDK / 1x1 GIF）
        │ POST /api/sitestat/collect (公开, throttle:120,1)
        ▼
┌────────────────── CollectController ──────────────────┐
│ CollectService: 抽样检查 → 路径排除 → IP归属地解析      │
│ （依赖 cmspro.iplocation）→ UA解析 → 来源解析 → 入库    │
└─────────────────────────┬─────────────────────────────┘
                          ▼
┌────────────────── 原始数据表（visits）─────────────────┐
│ app_cmspro_sitestat_visits  （每日 03:00 按保留天数清理）│
└─────────────────────────┬─────────────────────────────┘
                          ▼
┌──────────── DailyAggregationService（每日 00:10）─────┐
│ 聚合出 5 张日统计表：                                  │
│ daily_stats / daily_pages / daily_regions /           │
│ daily_sources / daily_devices                          │
└─────────────────────────┬─────────────────────────────┘
                          ▼
┌──────────── StatService（报表查询，聚合表+实时补全）───┐
│ getDashboard / getRegionStats / getSourceStats /       │
│ getPageStats / getDeviceStats / getVisitorList          │
└───────────────────────────────────────────────────────┘
```

### 3.2 技术栈与关键设计

- **技术栈**：Laravel（PHP >= 8.3）+ MySQL + 原生 JS SDK；无第三方统计类依赖
- **双表模型（聚合 + 实时补全）**：查询日期范围非今天时优先读聚合表，聚合表缺失的日期（如聚合任务异常）自动从 `visits` 表实时补全，今日数据实时查询，保证报表准确性与查询性能
- **会话（UV）定义**：`session_id` 由 SDK 生成的 sid 或服务端 `md5(ip+ua+日期)` 兜底，同一天同一设备计为同一 UV
- **来源类型枚举**：`referer_type` 1=直接访问（含站内跳转，通过站点绑定域名判断）、2=搜索引擎（内置 11 个搜索引擎域名映射与关键词参数表）、3=外部链接
- **IP 归属地解析**：仅在 `enable_region_stat=1` 且 IP 库已配置时调用 `IpLocationService->searchToArray()`，失败降级为空字段并记录 warning 日志，不影响主流程
- **UA 解析**：`UaParserService` 内置 18 种浏览器、13 种操作系统、设备类型（mobile/tablet/desktop）与 33 种蜘蛛 UA 关键词映射，纯本地规则解析，无外部依赖
- **数据清理**：`data_retention_days` 控制原始访问记录保留天数（设为 0 不清理），聚合表数据永久保留
- **日期格式**：模型 `$casts` 统一输出 `Y-m-d H:i:s`，符合 CmsPro 日期规范

### 3.3 数据表结构

| 表名 | 用途 | 关键字段 |
|------|------|----------|
| `app_cmspro_sitestat_visits` | 原始访问记录 | session_id、ip、country/province/city/isp、url、path、referer_type、search_engine/keyword、browser、os、device_type、screen_resolution |
| `app_cmspro_sitestat_daily_stats` | 日汇总 | stat_date、pv、uv、ip_count、new_uv、bounce_rate、avg_duration |
| `app_cmspro_sitestat_daily_pages` | 页面日统计 | stat_date、path、title、pv、uv |
| `app_cmspro_sitestat_daily_regions` | 地域日统计 | stat_date、country、province、city、pv、uv |
| `app_cmspro_sitestat_daily_sources` | 来源日统计 | stat_date、referer_type、referer_domain、search_engine、search_keyword、pv、uv |
| `app_cmspro_sitestat_daily_devices` | 设备日统计 | stat_date、browser、os、device_type、screen_resolution、pv、uv |
| `app_cmspro_sitestat_spider_visits` | 蜘蛛访问记录 | spider_name、ip、url、path、user_agent、status |

> 蜘蛛访问记录的 `url` 字段最长 2000 字符，且 `SpiderService::sanitizeUrl()` 会对 URL 去噪（截断超长 URL、去除 `redirect=` 多层嵌套），防止蜘蛛递归跟踪导致数据膨胀（对应 `sql/V2026070201__alter_spider_visits_url_length.sql` 升级脚本）。

---

## 四、接口定义

### 4.1 公开采集接口

#### POST /api/sitestat/collect

- **认证**：公开（无需登录）
- **限流**：`throttle:120,1`（每分钟最多 120 次）
- **Content-Type**：`application/x-www-form-urlencoded` 或 FormData
- **请求参数**：

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| sid | string | 否 | 访客会话 ID（SDK 生成）；缺省服务端按 `md5(ip+ua+日期)` 兜底 |
| url | string | 否 | 当前页面完整 URL |
| path | string | 否 | 页面路径（用于排除规则匹配） |
| title | string | 否 | 页面标题 |
| referer | string | 否 | 来源页面 URL |
| ua | string | 否 | User-Agent（缺省取请求头） |
| screen | string | 否 | 屏幕分辨率，如 `1920x1080` |

- **返回值**：`image/gif` 1x1 透明 GIF（`Cache-Control: no-cache, no-store`；`Access-Control-Allow-Origin: *`）
- **处理流程**：抽样检查（抽样模式）→ 路径排除 → IP 归属地解析 → UA 解析 → 来源解析 → 写入 `visits` 表；命中排除规则或抽样不中时静默返回 GIF

#### GET /apps/cmspro.sitestat/js/sitestat.js

JS 统计 SDK 静态资源，通过 `SettingsController::apiJsSdkUrl()` 获取完整引入代码。

### 4.2 后台管理 API（前缀 `/admin/cmspro/sitestat`，需登录后台）

#### 统计查询类

| 方法 | 路径 | 控制器方法 | 通用参数 | 返回 data 说明 |
|------|------|-----------|----------|----------------|
| GET | `/api/dashboard` | DashboardController::apiDashboard | start_date、end_date（默认近 30 天） | `total_pv`、`total_uv`、`total_ip`、`avg_bounce_rate`、`today_pv`、`today_uv`、`yesterday_pv`、`yesterday_uv`、`trend[]` |
| GET | `/api/region` | RegionController::apiRegion | start_date、end_date、group_by（province/city）、page、limit | `total`、`list[]`（province/city、pv、uv）、`page`、`page_size` |
| GET | `/api/source` | SourceController::apiSource | start_date、end_date | `by_type[]`、`engines[]`、`external_links[]`（前20）、`keywords[]`（前20） |
| GET | `/api/page` | PageController::apiPage | start_date、end_date、page、limit | `total`、`list[]`（path、title、url、pv、uv）、分页信息 |
| GET | `/api/device` | DeviceController::apiDevice | start_date、end_date | `browsers[]`、`os[]`、`device_types[]`、`resolutions[]`（前10） |
| GET | `/api/visitor` | VisitorController::apiVisitor | start_date、end_date（默认近 7 天）、page、limit | `total`、`list[]`（原始访问记录）、分页信息 |
| GET | `/api/spider` | SpiderController::apiSpider | start_date、end_date | `total_visits`、`by_spider[]`、`daily_trend[]`、`recent_visits[]`（前50） |
| GET | `/api/spider/visits` | SpiderController::apiSpiderVisits | start_date、end_date（默认近 7 天）、spider_name、page、limit | `total`、`list[]`、分页信息 |
| POST | `/api/spider/block` | SpiderController::apiBlock | type（ip/ua）、value | `{code:0, message}`；将 IP/UA 加入黑名单 |
| POST | `/api/spider/unblock` | SpiderController::apiUnblock | type（ip/ua）、value | `{code:0, message}`；从黑名单移除 |
| GET | `/api/spider/blocked-list` | SpiderController::apiBlockedList | - | `{code:0, data:{ips[], uas[]}}` |

#### 设置类

| 方法 | 路径 | 控制器方法 | 参数 | 返回 |
|------|------|-----------|------|------|
| GET | `/api/settings` | SettingsController::apiGetSettings | - | `{code:0, data:{track_mode, sample_rate, exclude_paths, data_retention_days, enable_region_stat, spider_track_enabled, login_page_action, crawl_delay, custom_exclude_paths, blocked_spiders}}` |
| POST | `/api/settings` | SettingsController::apiSaveSettings | 上述字段（可按需提交） | `{code:0, message:"保存成功"}`；含蜘蛛字段时自动重新生成 robots.txt |
| GET | `/api/js-sdk-url` | SettingsController::apiJsSdkUrl | - | `{code:0, data:{sdk_url, embed_code}}` |

- **统一返回格式**：成功 `{code:0, data:...}`；异常返回非 0 code
- **视图路由**：`GET /dashboard`、`/region`、`/source`、`/page`、`/device`、`/visitor`、`/spider`、`/settings` 返回对应 Blade 页面

---

## 五、操作使用指南

### 5.1 环境要求

- CmsPro v5.0.0+，PHP >= 8.3
- 已安装并启用 `cmspro.iplocation` 应用，且已配置 CZDB IP 库与密钥（未配置时仅地域相关统计为空）
- 已配置 Laravel 调度器（crontab 每分钟执行 `php artisan schedule:run`），否则日聚合与数据清理任务不会自动执行

### 5.2 安装部署

1. 通过 CmsPro 后台「应用市场」安装本应用（系统自动校验 `cmspro.iplocation` 依赖）
2. 安装完成后确认 `app_cmspro_sitestat_*` 系列迁移已执行（Install 脚本自动执行 Migrations 目录下所有迁移）
3. 如升级到含蜘蛛 URL 长度变更的版本，安装脚本会自动执行 `Migrations/2026_07_02_000001_...` 迁移
4. 在「统计设置」页配置统计模式等参数，并获取 JS SDK 嵌入代码
5. 在目标站点页面引入 SDK，即可开始采集

### 5.3 日常操作流程

1. **日常流量监控**：进入「访问概览」查看今日/昨日 PV、UV 与趋势
2. **渠道效果分析**：进入「来源分析」查看搜索引擎、外部链接、搜索关键词排行
3. **内容运营**：进入「页面统计」识别高热度页面与低热度页面
4. **SEO 与反爬**：在「统计设置」配置蜘蛛追踪与屏蔽策略（保存后自动更新 robots.txt），进入「蜘蛛统计」查看搜索引擎抓取情况
5. **数据治理**：根据「原始数据保留天数」控制存储量；关闭地域统计可降低采集耗时

---

## 六、配置参数说明

### 6.1 配置来源

配置项定义于 `manifest.json` 的 `config_groups`，安装后写入系统配置表（code 前缀 `app_cmspro_sitestat_`）；代码默认值位于 `Config/sitestat.php`（config key：`apps.cmspro.sitestat`）。

### 6.2 基础统计配置（统计设置页）

| 配置项 code | 说明 | 类型 | 默认值 | 取值范围/说明 |
|-------------|------|------|--------|---------------|
| `track_mode` | 统计模式 | select | `full` | `full`=全量统计；`sample`=抽样统计 |
| `sample_rate` | 抽样率 | number | `10` | 1-100，抽样模式下记录百分之几的访问；基于 sid 做 crc32 取模判断 |
| `exclude_paths` | 排除路径 | textarea | 空 | 每行一个，支持 `*` 通配符（如 `/admin/*`、`/api/*`） |
| `data_retention_days` | 原始数据保留天数 | number | `90` | 超过天数自动清理原始访问与蜘蛛访问记录；0 表示不清理 |
| `enable_region_stat` | 开启地域统计 | switch | `1` | 1=开启；0=关闭（关闭后不再调用 IP 库解析） |

### 6.3 蜘蛛管理配置（统计设置页扩展字段）

以下配置项由设置页读写，保存后联动重新生成 `public/robots.txt`：

| 配置项 code | 说明 | 默认值 |
|-------------|------|--------|
| `spider_track_enabled` | 蜘蛛追踪开关 | `1`（默认开启） |
| `login_page_action` | 登录页（login/register/password）蜘蛛动作 | `allow`（另可选 `block_404`/`block_403`） |
| `crawl_delay` | 通用抓取间隔（秒，写入 robots.txt） | `10` |
| `custom_exclude_paths` | 自定义 robots.txt 排除路径 | 空 |
| `blocked_spiders` | 屏蔽蜘蛛列表（逗号分隔，内置 23 个蜘蛛名称映射） | 空 |
| `blocked_ips` | IP 黑名单（每行一个，精确匹配拦截） | 空 |
| `blocked_uas` | UA 黑名单（每行一个，子串匹配拦截） | 空 |

> 内置蜘蛛名称与 UA 映射：Google、百度、Bing、搜狗、360搜索、宜搜、Yandex、Amazon、Apple、今日头条、Semrush、Ahrefs、Majestic、华为花瓣、Claude、OpenAI、ChatGPT、Common Crawl、Perplexity、DuckDuckGo、Facebook、Twitter、LinkedIn、Telegram 等。
>
> `blocked_ips` 与 `blocked_uas` 由「蜘蛛统计」页的拉黑按钮或「统计设置」页的黑名单管理维护，不参与 robots.txt 生成，仅由中间件直接拦截（返回 403）。

### 6.4 配置示例

```
track_mode = full
sample_rate = 10
exclude_paths = /admin/*
/api/*
/user/*
data_retention_days = 90
enable_region_stat = 1
spider_track_enabled = 1
login_page_action = allow
crawl_delay = 10
blocked_spiders = Semrush,Ahrefs
```

---

## 七、常见问题解答

**Q1：报表数据比实际访问少？**
- 检查统计模式是否为抽样模式，抽样率过低会丢弃部分数据
- 检查访问路径是否命中 `exclude_paths` 排除规则
- 检查 `cmspro.iplocation` 是否已配置（未配置时采集流程会跳过 IP 解析，但不会丢 PV 数据）

**Q2：地域统计全部为空？**
- 确认「开启地域统计」开关为开启状态
- 确认 `cmspro.iplocation` 应用已启用且 CZDB 文件与密钥配置正确（后台「IP归属地」可查看状态）

**Q3：今日数据有，但昨天及之前的数据查不到？**
- 确认服务器已配置调度器（`php artisan schedule:run`），日聚合任务 `sitestat:daily-aggregate` 每日 00:10 执行
- 聚合任务失败会写日志（关键字「访问统计日聚合失败」）；未聚合的日期报表会从原始表实时补全

**Q4：蜘蛛没有被记录？**
- 确认「蜘蛛追踪开关」已开启
- 确认蜘蛛访问路径不在排除范围（后台/API/静态资源路径不记录）
- 同一蜘蛛同 IP 同路径 5 分钟内重复访问会被去重

**Q5：修改蜘蛛设置后 robots.txt 没有生效？**
- 保存设置时仅当提交了 `crawl_delay`、`custom_exclude_paths`、`blocked_spiders`、`spider_track_enabled` 任一字段才会重新生成 robots.txt
- 检查 `public/robots.txt` 目录是否可写（写入失败会记录 warning 日志）

**Q6：采集接口报 429？**
- `POST /api/sitestat/collect` 受每分钟 120 次限流保护；高流量站点建议改为抽样统计模式，或部署时调大限流阈值

---

## 更新日志

| 版本 | 日期 | 更新人 | 变更内容 |
|------|------|--------|----------|
| v1.0.0 | 2026-08-12 | 开发团队 | 初始版本 |
| v1.0.1 | 2026-08-18 | 开发团队 | IP/UA 黑名单读取加 `Cache::remember` 缓存（1h TTL），写入时自动清缓存 |

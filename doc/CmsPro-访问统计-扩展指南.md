# CmsPro-访问统计-扩展指南

> 版本：1.0.0 | 更新日期：2026-08-13
> 应用版本：1.0.1 | 应用 ID：cmspro.sitestat

---

## 一、概述

访问统计（CmsproSitestat）应用为其他应用提供访问数据采集、统计查询、蜘蛛识别等扩展能力。其他应用可通过其 Service 层、模型层进行调用，实现自定义统计报表、蜘蛛防护联动、UA 解析等场景。

**命名空间**：`App\Apps\CmsproSitestat\`

---

## 二、前置检查

在调用本应用扩展接口前，请确认：

1. 本应用已安装并启用（可通过 `manifest.json` 的 `dependencies` 字段声明依赖，或运行时检查应用状态）。
2. 若需使用 IP 归属地解析，需同时安装并启用 `cmspro.iplocation` 应用。
3. 若需读取配置项，注意配置项 code 前缀为 `app_cmspro_sitestat_`。

---

## 三、Service 层调用

### 3.1 服务类清单

| 服务类 | 命名空间 | 职责 |
|--------|----------|------|
| `CollectService` | `App\Apps\CmsproSitestat\Services\CollectService` | 采集数据处理（抽样、排除、IP 解析、UA 解析、来源解析、入库） |
| `StatService` | `App\Apps\CmsproSitestat\Services\StatService` | 统计报表查询（概览、地域、来源、页面、设备、访客明细、数据清理） |
| `SpiderService` | `App\Apps\CmsproSitestat\Services\SpiderService` | 蜘蛛采集、蜘蛛统计、频率控制、URL 去噪 |
| `DailyAggregationService` | `App\Apps\CmsproSitestat\Services\DailyAggregationService` | 日聚合（将原始数据聚合到日统计表） |
| `UaParserService` | `App\Apps\CmsproSitestat\Services\UaParserService` | UA 解析（浏览器/操作系统/设备类型/蜘蛛识别） |

### 3.2 公开方法

#### CollectService

| 方法 | 签名 | 说明 |
|------|------|------|
| `collect` | `collect(Request $request): bool` | 处理一次采集请求，返回是否成功入库 |

#### StatService

| 方法 | 签名 | 说明 |
|------|------|------|
| `getDashboard` | `getDashboard(string $startDate, string $endDate): array` | 访问概览数据 |
| `getRegionStats` | `getRegionStats(string $startDate, string $endDate, string $groupBy = 'province', int $page = 1, int $pageSize = 20): array` | 地域统计 |
| `getSourceStats` | `getSourceStats(string $startDate, string $endDate): array` | 来源分析 |
| `getPageStats` | `getPageStats(string $startDate, string $endDate, int $page = 1, int $pageSize = 20): array` | 页面统计 |
| `getDeviceStats` | `getDeviceStats(string $startDate, string $endDate): array` | 设备分析 |
| `getVisitorList` | `getVisitorList(string $startDate, string $endDate, int $page = 1, int $pageSize = 20): array` | 访客明细 |
| `cleanExpiredVisits` | `cleanExpiredVisits(): int` | 清理过期原始访问数据，返回删除条数 |

#### SpiderService

| 方法 | 签名 | 说明 |
|------|------|------|
| `collectFromRequest` | `collectFromRequest(Request $request): bool` | 从请求采集蜘蛛访问记录 |
| `getSpiderOverview` | `getSpiderOverview(string $startDate, string $endDate): array` | 蜘蛛统计概览 |
| `getSpiderVisitList` | `getSpiderVisitList(string $startDate, string $endDate, int $page = 1, int $pageSize = 20, ?string $spiderName = null): array` | 蜘蛛访问明细 |
| `cleanExpiredVisits` | `cleanExpiredVisits(): int` | 清理过期蜘蛛访问记录 |
| `detectSpider` | `detectSpider(string $ua): string` | 识别 UA 是否为蜘蛛，返回蜘蛛名称（非蜘蛛返回空字符串） |
| `isRateLimited` | `isRateLimited(string $spiderName, string $ip): bool` | 频率控制：同一蜘蛛/IP 在时间窗口内是否超限 |
| `isExcludedPath` | `isExcludedPath(string $path): bool` | 判断路径是否在蜘蛛排除范围内 |
| `getBlockedIps` | `getBlockedIps(): array` | 读取 IP 黑名单（每行一个） |
| `getBlockedUas` | `getBlockedUas(): array` | 读取 UA 黑名单（每行一个） |
| `isIpBlocked` | `isIpBlocked(string $ip): bool` | 判断 IP 是否在黑名单中（精确匹配） |
| `isUaBlocked` | `isUaBlocked(string $ua): bool` | 判断 UA 是否在黑名单中（子串匹配） |
| `blockIp` | `blockIp(string $ip): bool` | 将 IP 加入黑名单，返回是否新增 |
| `blockUa` | `blockUa(string $ua): bool` | 将 UA 加入黑名单，返回是否新增 |
| `unblock` | `unblock(string $type, string $value): bool` | 从黑名单移除指定值（type 为 ip/ua） |

#### DailyAggregationService

| 方法 | 签名 | 说明 |
|------|------|------|
| `aggregate` | `aggregate(?string $date = null): void` | 执行日聚合，默认聚合昨天，可指定日期 |

#### UaParserService

| 方法 | 签名 | 说明 |
|------|------|------|
| `parse` | `parse(string $ua): array` | 完整解析 UA，返回 `browser`、`os`、`device_type` |
| `parseBrowser` | `parseBrowser(string $ua): string` | 解析浏览器 |
| `parseOs` | `parseOs(string $ua): string` | 解析操作系统 |
| `parseDeviceType` | `parseDeviceType(string $ua): string` | 解析设备类型（mobile/tablet/desktop） |
| `detectSpider` | `detectSpider(string $ua): string` | 识别蜘蛛 |

### 3.3 调用示例

```php
<?php

use App\Apps\CmsproSitestat\Services\StatService;
use App\Apps\CmsproSitestat\Services\SpiderService;
use App\Apps\CmsproSitestat\Services\UaParserService;

// 通过容器解析服务
$statService = app(StatService::class);

// 获取近 30 天访问概览
$dashboard = $statService->getDashboard(
    now()->subDays(30)->toDateString(),
    now()->toDateString()
);

// 获取地域统计（按省份分组）
$regions = $statService->getRegionStats(
    now()->subDays(30)->toDateString(),
    now()->toDateString(),
    'province',
    1,
    20
);

// 识别 UA 是否为蜘蛛
$uaParser = app(UaParserService::class);
$spiderName = $uaParser->detectSpider($request->userAgent() ?? '');
if (!empty($spiderName)) {
    // 是蜘蛛，可做自定义处理
}

// 频率控制判断
$spiderService = app(SpiderService::class);
if ($spiderService->isRateLimited($spiderName, $request->ip())) {
    // 蜘蛛访问超限，返回 429
}
```

---

## 四、钩子扩展点

本应用当前未注册自定义钩子（Hook）扩展点。若需在采集、聚合等环节做扩展，可通过以下方式：

- **采集扩展**：直接调用 `CollectService::collect()` 或 `SpiderService::collectFromRequest()` 手动触发采集。
- **聚合扩展**：直接调用 `DailyAggregationService::aggregate($date)` 手动触发指定日期的聚合。
- **蜘蛛防护扩展**：复用 `SpiderService::isRateLimited()`、`isExcludedPath()`、`detectSpider()` 实现自定义防护逻辑。

---

## 五、辅助函数

本应用未提供全局辅助函数（helper）。所有扩展能力均通过 Service 层方法暴露。

---

## 六、模型引用

本应用模型均继承 `App\Models\BaseModel`，命名空间为 `App\Apps\CmsproSitestat\Models`：

| 模型 | 数据表 | 说明 |
|------|--------|------|
| `Visit` | `app_cmspro_sitestat_visits` | 原始访问记录 |
| `DailyStat` | `app_cmspro_sitestat_daily_stats` | 日汇总 |
| `DailyPage` | `app_cmspro_sitestat_daily_pages` | 页面日统计 |
| `DailyRegion` | `app_cmspro_sitestat_daily_regions` | 地域日统计 |
| `DailySource` | `app_cmspro_sitestat_daily_sources` | 来源日统计 |
| `DailyDevice` | `app_cmspro_sitestat_daily_devices` | 设备日统计 |
| `SpiderVisit` | `app_cmspro_sitestat_spider_visits` | 蜘蛛访问记录 |

> 注意：模型日期字段通过 `$casts` 统一输出 `Y-m-d H:i:s` 格式，符合 CmsPro 日期规范。直接读取模型日期字段即可，无需二次格式化。

### 模型引用示例

```php
<?php

use App\Apps\CmsproSitestat\Models\Visit;

// 查询今日 PV
$todayPv = Visit::whereDate('create_time', now()->toDateString())->count();

// 查询某路径的访问记录
$visits = Visit::where('path', '/article/1')->orderByDesc('create_time')->limit(10)->get();
```

---

## 七、注意事项

1. **配置项前缀**：本应用配置项 code 前缀为 `app_cmspro_sitestat_`（如 `app_cmspro_sitestat_track_mode`、`app_cmspro_sitestat_data_retention_days`），可通过 `App\Models\ConfigItem` 读取。
2. **IP 归属地依赖**：`CollectService` 依赖 `cmspro.iplocation` 应用的 `IpLocationService`，若未安装或未配置，地域字段为空但不影响主流程。
3. **会话（UV）定义**：`session_id` 由 SDK 生成的 sid 或服务端 `md5(ip+ua+日期)` 兜底，同一天同一设备计为同一 UV。
4. **来源类型枚举**：`referer_type` 1=直接访问、2=搜索引擎、3=外部链接。
5. **数据清理**：`data_retention_days` 控制原始访问记录保留天数（设为 0 不清理），聚合表数据永久保留。
6. **日期格式**：所有日期参数建议使用 `Y-m-d` 格式字符串传入。
7. **性能**：统计查询采用「聚合表 + 实时补全」双表模型，大时间范围查询优先读聚合表，请勿直接对原始 `visits` 表做大范围聚合查询。

---

## 八、版本与更新日志

| 版本 | 日期 | 变更内容 |
|------|------|----------|
| 1.0.1 | 2026-08-13 | 当前版本（含蜘蛛 URL 长度调整迁移） |
| 1.0.0 | 2026-08-12 | 初始版本 |

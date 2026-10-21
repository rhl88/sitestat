# 访问统计 应用文档

> 应用ID：`cmspro.sitestat` | 文档版本：1.0.4 | 应用版本：1.0.4 | 依赖：PHP ≥ 8.3, CmsPro ≥ 5.0.0

- 官方地址：https://www.cmspro.cn/apps/cmspro.sitestat
- 本应用基于 CMSPRO 运行，请先安装 [CMSPRO](https://www.cmspro.cn/help/2)

## 1. 应用概述

CmsPro 自研的网站访问统计分析应用，为站点提供全面的访问数据统计与分析能力：

- **六大统计维度**：PV/UV 总览与趋势、IP 地域分布（国家/省份/城市）、来源分析（搜索引擎/外部链接/关键词）、页面热度、设备浏览器统计、蜘蛛爬虫追踪
- **采集机制**：自研 JS SDK（`/apps/cmspro.sitestat/js/sitestat.js`）经 `POST /api/sitestat/collect` 上报（sendBeacon 优先、同步 XHR 降级、SPA 路由监听），服务端统一净化外部输入（非 UTF-8 转 UTF-8、非法字节清除、超长截断），兼容任意编码页面
- **蜘蛛防护**：七层防护中间件（URL 嵌套/登录页动作/黑名单/频率控制/响应头引导）+ robots.txt 自动生成，支持 IP/UA 拉黑
- **数据架构**：原始访问表 + 5 张日聚合表（定时任务每日聚合与清理），聚合表优先、实时补全，保证大数据量报表性能
- **依赖应用**：`cmspro.iplocation`（IP 归属地解析；未配置时地域字段为空，不影响其他统计）

## 2. 目录结构

```
CmsproSitestat/
├── Assets/js/sitestat.js               # 前端统计 SDK
├── Config/sitestat.php                 # 默认配置值
├── Controllers/                        # 控制器（采集/概览/地域/来源/页面/设备/访客/蜘蛛/设置）
├── Middleware/SpiderTrackMiddleware.php # 蜘蛛追踪与防护中间件（注册进 web 组）
├── Models/                             # 模型（Visit 原始访问 + Daily* 日聚合 + SpiderVisit 蜘蛛）
├── Routes/
│   ├── admin.php                       # 后台管理路由
│   └── api.php                         # 公开采集路由（throttle:120,1）
├── Services/
│   ├── CollectService.php              # 采集入库（抽样/路径排除/地域/UA/来源解析/输入净化）
│   ├── DailyAggregationService.php     # 每日聚合
│   ├── SpiderService.php               # 蜘蛛记录
│   ├── StatService.php                 # 报表查询（聚合表+实时补全）
│   └── UaParserService.php             # UA 本地解析
├── Migrations/                         # 应用表迁移（app_cmspro_sitestat_ 前缀）
├── Tests/                              # 应用功能测试（采集编码净化等）
├── Views/                              # 后台视图（概览/地域/来源/页面/设备/访客/设置）
├── sql/                                # 数据库升级 SQL 脚本
├── Install.php                         # 安装/卸载/升级逻辑
├── manifest.json                       # 应用元数据唯一声明源
└── doc/                                # 应用文档
```

## 3. 应用文档

| 文档 | 说明 |
|------|------|
| [CmsPro-访问统计应用文档.md](doc/CmsPro-访问统计应用文档.md) | 功能、架构、接口、配置、FAQ |
| [CmsPro-访问统计-使用指南.md](doc/CmsPro-访问统计-使用指南.md) | 安装、配置、功能验证指南 |
| [CmsPro-访问统计-扩展指南.md](doc/CmsPro-访问统计-扩展指南.md) | Service 调用等扩展接口说明 |

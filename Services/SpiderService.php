<?php

namespace App\Apps\CmsproSitestat\Services;

use App\Apps\CmsproSitestat\Models\SpiderVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpiderService
{
    public function __construct(
        protected UaParserService $uaParser
    ) {}

    /**
     * URL 去噪：截断过长 URL，去除可能导致无限递归的 redirect 参数嵌套
     */
    protected function sanitizeUrl(string $url, string $path): string
    {
        // 裁剪到最大长度
        $url = mb_substr($url, 0, 2000);

        // 如果 URL 出现多次 redirect 参数嵌套（同一参数重复出现 >= 3 次），
        // 说明是蜘蛛在递归跟踪带有 redirect 的地址，只保留第一层 redirect
        if (substr_count($url, 'redirect=') >= 3) {
            // 解析原始 URL，提取第一层 redirect 目标
            $parsed = parse_url($url);
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query);
                if (!empty($query['redirect'])) {
                    // 只保留原始 redirect 参数值（不继续解码），并将多层嵌套替换为原始值
                    $firstRedirect = $query['redirect'];
                    // 重新构建 URL，仅保留第一层 redirect
                    $baseUrl = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '');
                    if (!empty($parsed['port'])) {
                        $baseUrl .= ':' . $parsed['port'];
                    }
                    $newQuery = http_build_query(array_merge($query, ['redirect' => $firstRedirect]));
                    $url = $baseUrl . ($parsed['path'] ?? '/') . '?' . $newQuery;
                }
            }
        }

        return $url;
    }

    /**
     * 服务端采集蜘蛛访问记录
     * 在中间件中调用，检测当前请求是否来自蜘蛛
     */
    public function collectFromRequest(Request $request): bool
    {
        $ua = $request->userAgent() ?? '';
        $spiderName = $this->uaParser->detectSpider($ua);

        if (empty($spiderName)) {
            return false;
        }

        // 排除后台和API路径
        $path = $request->path();
        if ($this->isExcludedPath($path)) {
            return false;
        }

        try {
            // 对 URL 进行去噪处理
            $url = $this->sanitizeUrl($request->fullUrl(), $path);

            // 重复检测：同蜘蛛、同IP、同路径在最近 N 秒内已记录则跳过
            $recent = SpiderVisit::where('spider_name', $spiderName)
                ->where('ip', $request->ip())
                ->where('path', '/' . $path)
                ->where('create_time', '>=', now()->subMinutes(5))
                ->exists();

            if ($recent) {
                return false;
            }

            SpiderVisit::create([
                'spider_name' => $spiderName,
                'ip' => $request->ip(),
                'url' => $url,
                'path' => mb_substr('/' . $path, 0, 255),
                'user_agent' => mb_substr($ua, 0, 500),
                'status' => 1,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('蜘蛛访问记录写入失败', [
                'app' => 'cmspro.sitestat',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 获取蜘蛛统计概览
     */
    public function getSpiderOverview(string $startDate, string $endDate): array
    {
        $query = SpiderVisit::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalVisits = (clone $query)->count();

        // 按蜘蛛名称统计
        $bySpider = (clone $query)
            ->selectRaw('spider_name, COUNT(*) as visit_count, COUNT(DISTINCT ip) as ip_count')
            ->groupBy('spider_name')
            ->orderByDesc('visit_count')
            ->get()
            ->toArray();

        // 按日期统计趋势
        $dailyTrend = (clone $query)
            ->selectRaw("DATE(create_time) as date, COUNT(*) as visit_count, COUNT(DISTINCT ip) as ip_count")
            ->groupByRaw('DATE(create_time)')
            ->orderBy('date')
            ->get()
            ->toArray();

        // 最近访问的蜘蛛
        $recentVisits = SpiderVisit::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderByDesc('create_time')
            ->limit(50)
            ->get()
            ->toArray();

        return [
            'total_visits' => $totalVisits,
            'by_spider' => $bySpider,
            'daily_trend' => $dailyTrend,
            'recent_visits' => $recentVisits,
        ];
    }

    /**
     * 获取蜘蛛访问明细列表
     */
    public function getSpiderVisitList(string $startDate, string $endDate, int $page = 1, int $pageSize = 20, ?string $spiderName = null): array
    {
        $query = SpiderVisit::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if (!empty($spiderName)) {
            $query->where('spider_name', $spiderName);
        }

        $total = $query->count();
        $list = $query->orderByDesc('create_time')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get()
            ->toArray();

        // 附加黑名单标记：IP 或 UA 命中黑名单则标记为已拉黑
        $blockedIps = $this->getBlockedIps();
        $blockedUas = $this->getBlockedUas();
        foreach ($list as &$item) {
            $ipBlocked = !empty($item['ip']) && in_array($item['ip'], $blockedIps, true);
            $uaBlocked = false;
            if (!empty($item['user_agent'])) {
                foreach ($blockedUas as $blocked) {
                    if ($blocked !== '' && strpos($item['user_agent'], $blocked) !== false) {
                        $uaBlocked = true;
                        break;
                    }
                }
            }
            $item['is_blocked'] = $ipBlocked || $uaBlocked;
        }
        unset($item);

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 清理过期蜘蛛访问记录
     */
    public function cleanExpiredVisits(): int
    {
        $days = (int) (\App\Models\ConfigItem::where('code', 'app_cmspro_sitestat_data_retention_days')->value('value') ?? 90);

        if ($days <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($days)->toDateString();

        return SpiderVisit::whereDate('create_time', '<', $cutoff)->delete();
    }

    /**
     * 蜘蛛识别（委托给 UaParserService）
     */
    public function detectSpider(string $ua): string
    {
        return $this->uaParser->detectSpider($ua);
    }

    /**
     * 频率控制：同一蜘蛛/IP 在时间窗口内是否超限
     */
    public function isRateLimited(string $spiderName, string $ip): bool
    {
        // 不同蜘蛛的频率阈值（次/分钟）
        $limits = [
            'Amazon'       => ['max' => 30,  'period' => 60],
            'Semrush'      => ['max' => 30,  'period' => 60],
            'Ahrefs'       => ['max' => 30,  'period' => 60],
            'Majestic'     => ['max' => 30,  'period' => 60],
            'Common Crawl' => ['max' => 60,  'period' => 60],
            'Google'       => ['max' => 300, 'period' => 60],
            'Bing'         => ['max' => 200, 'period' => 60],
            '百度'          => ['max' => 200, 'period' => 60],
            'Yandex'       => ['max' => 120, 'period' => 60],
            'Apple'        => ['max' => 120, 'period' => 60],
            '__default__'  => ['max' => 60,  'period' => 60],
        ];

        $limit = $limits[$spiderName] ?? $limits['__default__'];
        $cutoff = now()->subSeconds($limit['period']);

        $count = SpiderVisit::where('spider_name', $spiderName)
            ->where('ip', $ip)
            ->where('create_time', '>=', $cutoff)
            ->count();

        return $count >= $limit['max'];
    }

    /**
     * 读取 IP 黑名单（每行一个 IP）
     */
    public function getBlockedIps(): array
    {
        $str = \App\Models\ConfigItem::where('code', 'app_cmspro_sitestat_blocked_ips')->value('value') ?? '';
        return array_values(array_filter(array_map('trim', explode("\n", $str))));
    }

    /**
     * 读取 UA 黑名单（每行一个 UA）
     */
    public function getBlockedUas(): array
    {
        $str = \App\Models\ConfigItem::where('code', 'app_cmspro_sitestat_blocked_uas')->value('value') ?? '';
        return array_values(array_filter(array_map('trim', explode("\n", $str))));
    }

    /**
     * 判断 IP 是否在黑名单中
     */
    public function isIpBlocked(string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }
        return in_array($ip, $this->getBlockedIps(), true);
    }

    /**
     * 判断 UA 是否在黑名单中（支持子串匹配）
     */
    public function isUaBlocked(string $ua): bool
    {
        if (empty($ua)) {
            return false;
        }
        foreach ($this->getBlockedUas() as $blocked) {
            if ($blocked !== '' && strpos($ua, $blocked) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 将 IP 加入黑名单，返回是否新增
     */
    public function blockIp(string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }
        $list = $this->getBlockedIps();
        if (in_array($ip, $list, true)) {
            return false;
        }
        $list[] = $ip;
        $this->saveBlockedList('app_cmspro_sitestat_blocked_ips', $list);
        return true;
    }

    /**
     * 将 UA 加入黑名单，返回是否新增
     */
    public function blockUa(string $ua): bool
    {
        $ua = trim($ua);
        if (empty($ua)) {
            return false;
        }
        $list = $this->getBlockedUas();
        if (in_array($ua, $list, true)) {
            return false;
        }
        $list[] = $ua;
        $this->saveBlockedList('app_cmspro_sitestat_blocked_uas', $list);
        return true;
    }

    /**
     * 从黑名单移除指定值，返回是否移除成功
     */
    public function unblock(string $type, string $value): bool
    {
        $code = $type === 'ip' ? 'app_cmspro_sitestat_blocked_ips' : 'app_cmspro_sitestat_blocked_uas';
        $list = $type === 'ip' ? $this->getBlockedIps() : $this->getBlockedUas();

        $key = array_search($value, $list, true);
        if ($key === false) {
            return false;
        }
        unset($list[$key]);
        $this->saveBlockedList($code, array_values($list));
        return true;
    }

    /**
     * 保存黑名单列表（每行一个）
     */
    protected function saveBlockedList(string $code, array $list): void
    {
        $group = \App\Models\ConfigGroup::firstOrCreate(
            ['code' => 'app_cmspro_sitestat'],
            ['name' => '访问统计', 'app_id' => 'cmspro.sitestat', 'sort' => 0, 'status' => 1]
        );

        \App\Models\ConfigItem::updateOrCreate(
            ['code' => $code],
            [
                'value' => implode("\n", $list),
                'group_id' => $group->id,
                'name' => $code,
                'type' => 'textarea',
            ]
        );
    }

    /**
     * 排除路径检查
     */
    public function isExcludedPath(string $path): bool
    {
        // 排除后台、API、静态资源路径
        $excludePatterns = [
            'admin/*',
            'api/*',
            'login*',
            'register*',
            'password/*',
            'logout*',
            'telescope/*',
            '_debugbar/*',
            'horizon/*',
        ];

        foreach ($excludePatterns as $pattern) {
            $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
            if (preg_match($regex, $path)) {
                return true;
            }
        }

        // 排除静态资源文件
        if (preg_match('/\.(css|js|ico|png|jpg|jpeg|gif|svg|woff2?|ttf|eot|map)$/i', $path)) {
            return true;
        }

        return false;
    }
}

<?php

namespace App\Apps\CmsproSitestat\Services;

use App\Apps\CmsproSitestat\Models\DailyDevice;
use App\Apps\CmsproSitestat\Models\DailyPage;
use App\Apps\CmsproSitestat\Models\DailyRegion;
use App\Apps\CmsproSitestat\Models\DailySource;
use App\Apps\CmsproSitestat\Models\DailyStat;
use App\Apps\CmsproSitestat\Models\Visit;
use App\Models\ConfigItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatService
{
    /**
     * 获取访问概览数据
     */
    public function getDashboard(string $startDate, string $endDate): array
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        // 聚合表数据（不含今天，因为今天还没聚合）
        $aggregateEndDate = min($endDate, $yesterday);
        $dailyStats = collect();
        if ($startDate <= $aggregateEndDate) {
            $dailyStats = DailyStat::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->orderBy('stat_date', 'asc')
                ->get();
        }

        // 找出聚合表中缺失的日期，从visits表补全
        $existingDates = $dailyStats->map(function ($item) {
            return Carbon::parse($item->stat_date)->toDateString();
        })->toArray();

        $missingDailyStats = collect();
        if ($startDate <= $aggregateEndDate) {
            $query = Visit::select(
                    DB::raw('DATE(create_time) as stat_date'),
                    DB::raw('COUNT(*) as pv'),
                    DB::raw('COUNT(DISTINCT session_id) as uv'),
                    DB::raw('COUNT(DISTINCT ip) as ip_count')
                )
                ->whereBetween('create_time', [$startDate . ' 00:00:00', $aggregateEndDate . ' 23:59:59']);

            if (!empty($existingDates)) {
                $query->whereNotIn(DB::raw('DATE(create_time)'), $existingDates);
            }

            $missingDailyStats = $query->groupBy(DB::raw('DATE(create_time)'))->get();
        }

        // 今日实时数据（从visits表直接查）
        $todayPv = 0;
        $todayUv = 0;
        $todayIpCount = 0;
        $todayBounceRate = 0;
        if ($startDate <= $today && $endDate >= $today) {
            $todayPv = Visit::whereDate('create_time', $today)->count();
            $todayUv = Visit::whereDate('create_time', $today)->distinct('session_id')->count('session_id');
            $todayIpCount = Visit::whereDate('create_time', $today)->distinct('ip')->count('ip');
            $todayBounceRate = $this->calcTodayBounceRate($today);
        }

        // 昨日数据：优先从聚合表取，取不到则从visits实时查
        $yesterdayStat = DailyStat::where('stat_date', $yesterday)->first();
        if (!$yesterdayStat && $startDate <= $yesterday && $endDate >= $yesterday) {
            $yesterdayStat = (object) [
                'pv' => Visit::whereDate('create_time', $yesterday)->count(),
                'uv' => Visit::whereDate('create_time', $yesterday)->distinct('session_id')->count('session_id'),
            ];
        }

        // 汇总数据 = 聚合表累计 + 缺失日期补全 + 今日实时
        $totalPv = $dailyStats->sum('pv') + $missingDailyStats->sum('pv') + $todayPv;
        $totalUv = $dailyStats->sum('uv') + $missingDailyStats->sum('uv') + $todayUv;
        $totalIp = $dailyStats->sum('ip_count') + $missingDailyStats->sum('ip_count') + $todayIpCount;

        // 平均跳出率：仅基于聚合表数据（加权平均），缺失日期无法高效计算跳出率
        $bounceSum = $dailyStats->sum('bounce_rate');
        $bounceCount = $dailyStats->count();
        if ($todayPv > 0) {
            $bounceSum += $todayBounceRate;
            $bounceCount += 1;
        }
        $avgBounceRate = $bounceCount > 0 ? round($bounceSum / $bounceCount, 2) : 0;

        // 趋势数据：聚合表 + 缺失日期补全 + 今日实时
        $trend = $dailyStats->map(function ($item) {
            return [
                'date' => $item->stat_date->format('Y-m-d'),
                'pv' => $item->pv,
                'uv' => $item->uv,
                'ip_count' => $item->ip_count,
            ];
        })->toArray();

        foreach ($missingDailyStats as $item) {
            $trend[] = [
                'date' => $item->stat_date,
                'pv' => (int) $item->pv,
                'uv' => (int) $item->uv,
                'ip_count' => (int) $item->ip_count,
            ];
        }

        // 按日期排序
        usort($trend, fn($a, $b) => strcmp($a['date'], $b['date']));

        // 如果日期范围包含今天，追加今日趋势点
        if ($startDate <= $today && $endDate >= $today && $todayPv > 0) {
            $trend[] = [
                'date' => $today,
                'pv' => $todayPv,
                'uv' => $todayUv,
                'ip_count' => $todayIpCount,
            ];
        }

        return [
            'total_pv' => $totalPv,
            'total_uv' => $totalUv,
            'total_ip' => $totalIp,
            'avg_bounce_rate' => $avgBounceRate,
            'today_pv' => $todayPv,
            'today_uv' => $todayUv,
            'yesterday_pv' => $yesterdayStat->pv ?? 0,
            'yesterday_uv' => $yesterdayStat->uv ?? 0,
            'trend' => $trend,
        ];
    }

    /**
     * 计算今日跳出率
     */
    protected function calcTodayBounceRate(string $today): float
    {
        $totalSessions = Visit::whereDate('create_time', $today)->distinct('session_id')->count('session_id');
        if ($totalSessions === 0) {
            return 0;
        }
        $singlePageSessions = Visit::whereDate('create_time', $today)
            ->select('session_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('session_id')
            ->having('cnt', '=', 1)
            ->get()
            ->count();

        return round(($singlePageSessions / $totalSessions) * 100, 2);
    }

    /**
     * 判断日期范围是否包含今天，返回聚合表查询的结束日期
     */
    protected function getAggregateEndDate(string $endDate): string
    {
        $today = Carbon::today()->toDateString();
        return min($endDate, Carbon::yesterday()->toDateString());
    }

    /**
     * 判断日期范围是否包含今天
     */
    protected function rangeIncludesToday(string $startDate, string $endDate): bool
    {
        $today = Carbon::today()->toDateString();
        return $startDate <= $today && $endDate >= $today;
    }

    /**
     * 获取聚合表中已有的日期列表，用于排除已聚合的日期
     */
    protected function getExistingAggregateDates(string $modelClass, string $startDate, string $aggregateEndDate): array
    {
        if ($startDate > $aggregateEndDate) {
            return [];
        }

        return $modelClass::whereBetween('stat_date', [$startDate, $aggregateEndDate])
            ->distinct('stat_date')
            ->pluck('stat_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();
    }

    /**
     * 获取地域统计数据
     */
    public function getRegionStats(string $startDate, string $endDate, string $groupBy = 'province', int $page = 1, int $pageSize = 20): array
    {
        $aggregateEndDate = $this->getAggregateEndDate($endDate);
        $today = Carbon::today()->toDateString();

        // 聚合表数据
        $aggregateData = collect();
        if ($startDate <= $aggregateEndDate) {
            $query = DailyRegion::whereBetween('stat_date', [$startDate, $aggregateEndDate]);
            if ($groupBy === 'province') {
                $aggregateData = $query->select('province', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                    ->groupBy('province')
                    ->orderByDesc('pv')
                    ->get();
            } else {
                $aggregateData = $query->select('province', 'city', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                    ->groupBy('province', 'city')
                    ->orderByDesc('pv')
                    ->get();
            }
        }

        // 获取聚合表中已有的日期，用于排除
        $existingDates = $this->getExistingAggregateDates(DailyRegion::class, $startDate, $aggregateEndDate);

        // 缺失日期+今日实时数据（从visits表补全）
        $realtimeData = collect();
        $realtimeEndDate = min($endDate, $today);
        if ($startDate <= $realtimeEndDate) {
            $realtimeQuery = Visit::whereBetween('create_time', [$startDate . ' 00:00:00', $realtimeEndDate . ' 23:59:59']);
            if (!empty($existingDates)) {
                $realtimeQuery->whereNotIn(DB::raw('DATE(create_time)'), $existingDates);
            }
            if ($groupBy === 'province') {
                $realtimeData = $realtimeQuery->select('province', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                    ->groupBy('province')
                    ->get();
            } else {
                $realtimeData = $realtimeQuery->select('province', 'city', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                    ->groupBy('province', 'city')
                    ->get();
            }
        }

        // 合并数据
        $merged = $this->mergeAggregatedWithRealtime($aggregateData, $realtimeData, $groupBy === 'province' ? 'province' : ['province', 'city']);

        // 按PV降序排序
        usort($merged, fn($a, $b) => $b['pv'] <=> $a['pv']);

        $total = count($merged);
        $list = array_slice($merged, ($page - 1) * $pageSize, $pageSize);

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取来源统计数据
     */
    public function getSourceStats(string $startDate, string $endDate): array
    {
        $aggregateEndDate = $this->getAggregateEndDate($endDate);
        $today = Carbon::today()->toDateString();

        // 聚合表数据
        $byType = collect();
        $engines = collect();
        $externalLinks = collect();
        $keywords = collect();

        if ($startDate <= $aggregateEndDate) {
            // 按来源类型汇总
            $byType = DailySource::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->select('referer_type', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('referer_type')
                ->orderByDesc('pv')
                ->get();

            // 搜索引擎排行
            $engines = DailySource::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->where('referer_type', 2)
                ->select('search_engine', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('search_engine')
                ->orderByDesc('pv')
                ->get();

            // 外部链接排行
            $externalLinks = DailySource::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->where('referer_type', 3)
                ->select('referer_domain', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('referer_domain')
                ->orderByDesc('pv')
                ->limit(20)
                ->get();

            // 搜索关键词排行
            $keywords = DailySource::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->where('referer_type', 2)
                ->where('search_keyword', '!=', '')
                ->select('search_keyword', 'search_engine', DB::raw('SUM(pv) as pv'))
                ->groupBy('search_keyword', 'search_engine')
                ->orderByDesc('pv')
                ->limit(20)
                ->get();
        }

        // 获取聚合表中已有的日期，用于排除
        $existingDates = $this->getExistingAggregateDates(DailySource::class, $startDate, $aggregateEndDate);

        // 缺失日期+今日实时数据
        $realtimeEndDate = min($endDate, $today);
        if ($startDate <= $realtimeEndDate) {
            $baseRealtimeQuery = Visit::whereBetween('create_time', [$startDate . ' 00:00:00', $realtimeEndDate . ' 23:59:59']);
            if (!empty($existingDates)) {
                $baseRealtimeQuery->whereNotIn(DB::raw('DATE(create_time)'), $existingDates);
            }

            // 按来源类型
            $realtimeByType = (clone $baseRealtimeQuery)
                ->select('referer_type', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('referer_type')
                ->get();
            $byType = $this->mergeByKey($byType, $realtimeByType, 'referer_type');

            // 搜索引擎
            $realtimeEngines = (clone $baseRealtimeQuery)
                ->where('referer_type', 2)
                ->select('search_engine', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('search_engine')
                ->get();
            $engines = $this->mergeByKey($engines, $realtimeEngines, 'search_engine');

            // 外部链接
            $realtimeExternalLinks = (clone $baseRealtimeQuery)
                ->where('referer_type', 3)
                ->select('referer_domain', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('referer_domain')
                ->get();
            $externalLinks = $this->mergeByKey($externalLinks, $realtimeExternalLinks, 'referer_domain');

            // 搜索关键词
            $realtimeKeywords = (clone $baseRealtimeQuery)
                ->where('referer_type', 2)
                ->where('search_keyword', '!=', '')
                ->select('search_keyword', 'search_engine', DB::raw('COUNT(*) as pv'))
                ->groupBy('search_keyword', 'search_engine')
                ->get();
            $keywords = $this->mergeByCompositeKey($keywords, $realtimeKeywords, ['search_keyword', 'search_engine']);
        }

        // 排序
        $byType = $byType->sortByDesc('pv')->values()->toArray();
        $engines = $engines->sortByDesc('pv')->values()->toArray();
        $externalLinks = $externalLinks->sortByDesc('pv')->take(20)->values()->toArray();
        $keywords = $keywords->sortByDesc('pv')->take(20)->values()->toArray();

        return [
            'by_type' => $byType,
            'engines' => $engines,
            'external_links' => $externalLinks,
            'keywords' => $keywords,
        ];
    }

    /**
     * 获取页面统计数据
     */
    public function getPageStats(string $startDate, string $endDate, int $page = 1, int $pageSize = 20): array
    {
        $aggregateEndDate = $this->getAggregateEndDate($endDate);
        $today = Carbon::today()->toDateString();

        // 聚合表数据
        $aggregateData = collect();
        if ($startDate <= $aggregateEndDate) {
            $aggregateData = DailyPage::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->select('path', 'title', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('path', 'title')
                ->orderByDesc('pv')
                ->get();
        }

        // 获取聚合表中已有的日期，用于排除
        $existingDates = $this->getExistingAggregateDates(DailyPage::class, $startDate, $aggregateEndDate);

        // 缺失日期+今日实时数据（包含完整URL）
        $realtimeData = collect();
        $realtimeEndDate = min($endDate, $today);
        if ($startDate <= $realtimeEndDate) {
            $realtimeQuery = Visit::whereBetween('create_time', [$startDate . ' 00:00:00', $realtimeEndDate . ' 23:59:59']);
            if (!empty($existingDates)) {
                $realtimeQuery->whereNotIn(DB::raw('DATE(create_time)'), $existingDates);
            }
            $realtimeData = $realtimeQuery->select('path', 'title', 'url', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('path', 'title', 'url')
                ->get();
        }

        // 合并（path+title作为复合分组键，确保title字段保留）
        $merged = $this->mergeAggregatedWithRealtime($aggregateData, $realtimeData, ['path', 'title']);

        // 为聚合表数据补充完整URL（从原始访问记录中取样本URL）
        $pathsNeedUrl = array_filter($merged, fn($item) => empty($item['url'] ?? ''));
        if (!empty($pathsNeedUrl)) {
            $pathList = array_column($pathsNeedUrl, 'path');
            $urlMap = Visit::whereIn('path', $pathList)
                ->select('path', 'url')
                ->groupBy('path', 'url')
                ->get()
                ->keyBy('path');
            foreach ($merged as &$item) {
                if (empty($item['url'] ?? '') && isset($urlMap[$item['path']])) {
                    $item['url'] = $urlMap[$item['path']]->url;
                }
            }
            unset($item);
        }

        usort($merged, fn($a, $b) => $b['pv'] <=> $a['pv']);

        $total = count($merged);
        $list = array_slice($merged, ($page - 1) * $pageSize, $pageSize);

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取设备统计数据
     */
    public function getDeviceStats(string $startDate, string $endDate): array
    {
        $aggregateEndDate = $this->getAggregateEndDate($endDate);
        $today = Carbon::today()->toDateString();

        // 浏览器排行
        $browsers = collect();
        $osList = collect();
        $deviceTypes = collect();
        $resolutions = collect();

        if ($startDate <= $aggregateEndDate) {
            $browsers = DailyDevice::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->select('browser', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('browser')
                ->orderByDesc('pv')
                ->get();

            $osList = DailyDevice::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->select('os', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('os')
                ->orderByDesc('pv')
                ->get();

            $deviceTypes = DailyDevice::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->select('device_type', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('device_type')
                ->orderByDesc('pv')
                ->get();

            $resolutions = DailyDevice::whereBetween('stat_date', [$startDate, $aggregateEndDate])
                ->select('screen_resolution', DB::raw('SUM(pv) as pv'), DB::raw('SUM(uv) as uv'))
                ->groupBy('screen_resolution')
                ->orderByDesc('pv')
                ->limit(10)
                ->get();
        }

        // 获取聚合表中已有的日期，用于排除
        $existingDates = $this->getExistingAggregateDates(DailyDevice::class, $startDate, $aggregateEndDate);

        // 缺失日期+今日实时数据
        $realtimeEndDate = min($endDate, $today);
        if ($startDate <= $realtimeEndDate) {
            $baseRealtimeQuery = Visit::whereBetween('create_time', [$startDate . ' 00:00:00', $realtimeEndDate . ' 23:59:59']);
            if (!empty($existingDates)) {
                $baseRealtimeQuery->whereNotIn(DB::raw('DATE(create_time)'), $existingDates);
            }

            $realtimeBrowsers = (clone $baseRealtimeQuery)
                ->select('browser', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('browser')
                ->get();
            $browsers = $this->mergeByKey($browsers, $realtimeBrowsers, 'browser');

            $realtimeOs = (clone $baseRealtimeQuery)
                ->select('os', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('os')
                ->get();
            $osList = $this->mergeByKey($osList, $realtimeOs, 'os');

            $realtimeDeviceTypes = (clone $baseRealtimeQuery)
                ->select('device_type', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('device_type')
                ->get();
            $deviceTypes = $this->mergeByKey($deviceTypes, $realtimeDeviceTypes, 'device_type');

            $realtimeResolutions = (clone $baseRealtimeQuery)
                ->select('screen_resolution', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
                ->groupBy('screen_resolution')
                ->get();
            $resolutions = $this->mergeByKey($resolutions, $realtimeResolutions, 'screen_resolution');
        }

        return [
            'browsers' => $browsers->sortByDesc('pv')->values()->toArray(),
            'os' => $osList->sortByDesc('pv')->values()->toArray(),
            'device_types' => $deviceTypes->sortByDesc('pv')->values()->toArray(),
            'resolutions' => $resolutions->sortByDesc('pv')->take(10)->values()->toArray(),
        ];
    }

    /**
     * 获取访客明细列表
     */
    public function getVisitorList(string $startDate, string $endDate, int $page = 1, int $pageSize = 20): array
    {
        $query = Visit::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $total = $query->count();
        $list = $query->orderByDesc('create_time')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 清理过期原始访问数据
     */
    public function cleanExpiredVisits(): int
    {
        $days = (int) (ConfigItem::where('code', 'app_cmspro_sitestat_data_retention_days')->value('value') ?? 90);

        if ($days <= 0) {
            return 0;
        }

        $cutoff = Carbon::now()->subDays($days)->toDateString();

        return Visit::whereDate('create_time', '<', $cutoff)->delete();
    }

    /**
     * 合并聚合表数据与今日实时数据（单字段分组）
     */
    protected function mergeAggregatedWithRealtime($aggregateData, $realtimeData, $groupKeys): array
    {
        if (is_string($groupKeys)) {
            $groupKeys = [$groupKeys];
        }

        $merged = [];

        // 聚合表数据写入
        foreach ($aggregateData as $item) {
            $key = implode('|', array_map(fn($k) => $item->$k ?? $item[$k] ?? '', $groupKeys));
            $merged[$key] = [];
            foreach ($groupKeys as $gk) {
                $merged[$key][$gk] = $item->$gk ?? $item[$gk] ?? '';
            }
            $merged[$key]['pv'] = (int) ($item->pv ?? $item['pv'] ?? 0);
            $merged[$key]['uv'] = (int) ($item->uv ?? $item['uv'] ?? 0);
        }

        // 实时数据合并
        foreach ($realtimeData as $item) {
            $key = implode('|', array_map(fn($k) => $item->$k ?? $item[$k] ?? '', $groupKeys));
            if (isset($merged[$key])) {
                $merged[$key]['pv'] += (int) ($item->pv ?? $item['pv'] ?? 0);
                $merged[$key]['uv'] += (int) ($item->uv ?? $item['uv'] ?? 0);
            } else {
                $merged[$key] = [];
                foreach ($groupKeys as $gk) {
                    $merged[$key][$gk] = $item->$gk ?? $item[$gk] ?? '';
                }
                $merged[$key]['pv'] = (int) ($item->pv ?? $item['pv'] ?? 0);
                $merged[$key]['uv'] = (int) ($item->uv ?? $item['uv'] ?? 0);
            }
        }

        return array_values($merged);
    }

    /**
     * 按单字段分组合并两个集合的pv/uv
     */
    protected function mergeByKey($collection1, $collection2, string $key): \Illuminate\Support\Collection
    {
        $map = [];

        foreach ($collection1 as $item) {
            $k = $item->$key ?? $item[$key] ?? '';
            $map[$k] = [
                $key => $k,
                'pv' => (int) ($item->pv ?? $item['pv'] ?? 0),
                'uv' => (int) ($item->uv ?? $item['uv'] ?? 0),
            ];
        }

        foreach ($collection2 as $item) {
            $k = $item->$key ?? $item[$key] ?? '';
            if (isset($map[$k])) {
                $map[$k]['pv'] += (int) ($item->pv ?? $item['pv'] ?? 0);
                $map[$k]['uv'] += (int) ($item->uv ?? $item['uv'] ?? 0);
            } else {
                $map[$k] = [
                    $key => $k,
                    'pv' => (int) ($item->pv ?? $item['pv'] ?? 0),
                    'uv' => (int) ($item->uv ?? $item['uv'] ?? 0),
                ];
            }
        }

        return collect(array_values($map));
    }

    /**
     * 按复合字段分组合并两个集合的pv
     */
    protected function mergeByCompositeKey($collection1, $collection2, array $keys): \Illuminate\Support\Collection
    {
        $map = [];

        foreach ($collection1 as $item) {
            $k = implode('|', array_map(fn($key) => $item->$key ?? $item[$key] ?? '', $keys));
            $entry = [];
            foreach ($keys as $key) {
                $entry[$key] = $item->$key ?? $item[$key] ?? '';
            }
            $entry['pv'] = (int) ($item->pv ?? $item['pv'] ?? 0);
            $map[$k] = $entry;
        }

        foreach ($collection2 as $item) {
            $k = implode('|', array_map(fn($key) => $item->$key ?? $item[$key] ?? '', $keys));
            if (isset($map[$k])) {
                $map[$k]['pv'] += (int) ($item->pv ?? $item['pv'] ?? 0);
            } else {
                $entry = [];
                foreach ($keys as $key) {
                    $entry[$key] = $item->$key ?? $item[$key] ?? '';
                }
                $entry['pv'] = (int) ($item->pv ?? $item['pv'] ?? 0);
                $map[$k] = $entry;
            }
        }

        return collect(array_values($map));
    }
}

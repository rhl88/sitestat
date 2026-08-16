<?php

namespace App\Apps\CmsproSitestat\Services;

use App\Apps\CmsproIplocation\Services\IpLocationService;
use App\Apps\CmsproSitestat\Models\Visit;
use App\Enums\Status;
use App\Models\ConfigItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CollectService
{
    // 已知搜索引擎域名映射
    private const SEARCH_ENGINES = [
        'baidu.com' => '百度',
        'google.com' => 'Google',
        'google.com.hk' => 'Google',
        'google.cn' => 'Google',
        'bing.com' => 'Bing',
        'sogou.com' => '搜狗',
        'so.com' => '360搜索',
        'shenma.cn' => '神马',
        'yandex.com' => 'Yandex',
        'yahoo.com' => 'Yahoo',
        'duckduckgo.com' => 'DuckDuckGo',
    ];

    // 搜索引擎的查询参数名
    private const SEARCH_QUERY_PARAMS = [
        'baidu.com' => 'wd',
        'google.com' => 'q',
        'google.com.hk' => 'q',
        'google.cn' => 'q',
        'bing.com' => 'q',
        'sogou.com' => 'query',
        'so.com' => 'q',
        'shenma.cn' => 'q',
        'yandex.com' => 'text',
        'yahoo.com' => 'p',
        'duckduckgo.com' => 'q',
    ];

    public function __construct(
        protected UaParserService $uaParser
    ) {}

    /**
     * 处理采集数据
     */
    public function collect(Request $request): bool
    {
        // 抽样检查
        if (!$this->shouldTrack($request)) {
            return false;
        }

        // 路径排除检查
        $path = $request->input('path', '');
        if ($this->isExcludedPath($path)) {
            return false;
        }

        $ip = $request->ip();
        $ua = $request->input('ua', $request->userAgent() ?? '');
        $referer = $request->input('referer') ?? '';

        // 解析IP归属地
        $location = $this->resolveIpLocation($ip);

        // 解析UA
        $uaInfo = $this->uaParser->parse($ua);

        // 解析来源（传入当前页面URL用于判断站内跳转）
        $currentPageUrl = $request->input('url', '');
        $sourceInfo = $this->parseReferer($referer, $currentPageUrl);

        // 生成会话ID（基于IP+UA+日期，同一天同一设备视为同一会话）
        $sessionId = $request->input('sid', '');
        if (empty($sessionId)) {
            $sessionId = md5($ip . $ua . date('Y-m-d'));
        }

        try {
            Visit::create([
                'session_id' => $sessionId,
                'ip' => $ip,
                'country' => $location['country'] ?? '',
                'province' => $location['province'] ?? '',
                'city' => $location['city'] ?? '',
                'isp' => $location['isp'] ?? '',
                'url' => $request->input('url', ''),
                'path' => $path,
                'title' => $request->input('title', ''),
                'referer' => $referer,
                'referer_domain' => $sourceInfo['domain'],
                'referer_type' => $sourceInfo['type'],
                'search_engine' => $sourceInfo['engine'],
                'search_keyword' => $sourceInfo['keyword'],
                'user_agent' => mb_substr($ua, 0, 500),
                'browser' => $uaInfo['browser'],
                'os' => $uaInfo['os'],
                'device_type' => $uaInfo['device_type'],
                'screen_resolution' => $request->input('screen', ''),
                'status' => 1,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('访问统计数据写入失败', [
                'app' => 'cmspro.sitestat',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 抽样检查
     */
    protected function shouldTrack(Request $request): bool
    {
        $trackMode = ConfigItem::where('code', 'app_cmspro_sitestat_track_mode')->value('value') ?? 'full';

        if ($trackMode === 'full') {
            return true;
        }

        // 抽样模式：基于session_id做hash取模
        $sampleRate = (int) (ConfigItem::where('code', 'app_cmspro_sitestat_sample_rate')->value('value') ?? 10);
        $sampleRate = max(1, min(100, $sampleRate));
        $sid = $request->input('sid', $request->ip());
        $hash = crc32($sid) % 100;

        return $hash < $sampleRate;
    }

    /**
     * 路径排除检查
     */
    protected function isExcludedPath(string $path): bool
    {
        $excludePaths = ConfigItem::where('code', 'app_cmspro_sitestat_exclude_paths')->value('value') ?? '';
        if (empty($excludePaths)) {
            return false;
        }

        $patterns = array_filter(array_map('trim', explode("\n", $excludePaths)));
        foreach ($patterns as $pattern) {
            $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 解析IP归属地
     */
    protected function resolveIpLocation(string $ip): array
    {
        $enableRegion = ConfigItem::where('code', 'app_cmspro_sitestat_enable_region_stat')->value('value') ?? '1';

        if ($enableRegion !== '1') {
            return ['country' => '', 'province' => '', 'city' => '', 'isp' => ''];
        }

        try {
            $ipService = app(IpLocationService::class);
            if ($ipService->isConfigured()) {
                return $ipService->searchToArray($ip);
            }
        } catch (\Throwable $e) {
            Log::warning('访问统计IP归属地解析失败', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return ['country' => '', 'province' => '', 'city' => '', 'isp' => ''];
    }

    /**
     * 获取站点所有绑定域名（含主域名），缓存1小时
     */
    protected function getSiteDomains(): array
    {
        return Cache::remember('cmspro_sitestat_site_domains', 3600, function () {
            $domains = [];

            // 从 APP_URL 获取主域名
            $appUrl = config('app.url');
            if ($appUrl) {
                $parsed = parse_url($appUrl);
                if (!empty($parsed['host'])) {
                    $domains[] = $parsed['host'];
                }
            }

            // 从配置项获取所有应用绑定的域名
            $domainConfigs = ConfigItem::where('code', 'like', '%_access_domain')
                ->where('value', '!=', '')
                ->whereNotNull('value')
                ->where('status', Status::ENABLED)
                ->pluck('value');

            foreach ($domainConfigs as $config) {
                $configDomains = array_map('trim', explode(',', $config));
                foreach ($configDomains as $domain) {
                    if (!empty($domain) && !in_array($domain, $domains)) {
                        $domains[] = $domain;
                    }
                }
            }

            return $domains;
        });
    }

    /**
     * 判断域名是否为站内域名
     */
    protected function isSiteDomain(string $domain, string $currentPageUrl): bool
    {
        // 先与当前页面域名比较（最常见场景，无需查缓存）
        $currentParsed = parse_url($currentPageUrl);
        $currentHost = $currentParsed['host'] ?? '';
        if ($domain === $currentHost) {
            return true;
        }

        // 再与系统所有绑定域名比较
        $siteDomains = $this->getSiteDomains();
        foreach ($siteDomains as $siteDomain) {
            // 精确匹配或子域名匹配（如 siteDomain=example.com，domain=www.example.com）
            if ($domain === $siteDomain || str_ends_with($domain, '.' . $siteDomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 解析来源信息
     */
    protected function parseReferer(string $referer, string $currentPageUrl = ''): array
    {
        if (empty($referer)) {
            return ['type' => 1, 'domain' => '', 'engine' => '', 'keyword' => ''];
        }

        $parsed = parse_url($referer);
        $domain = $parsed['host'] ?? '';

        if (empty($domain)) {
            return ['type' => 1, 'domain' => '', 'engine' => '', 'keyword' => ''];
        }

        // 判断是否为站内来源（站内页面跳转视为直接访问）
        if (!empty($currentPageUrl) && $this->isSiteDomain($domain, $currentPageUrl)) {
            return ['type' => 1, 'domain' => '', 'engine' => '', 'keyword' => ''];
        }

        // 检查是否来自搜索引擎
        foreach (self::SEARCH_ENGINES as $engineDomain => $engineName) {
            if (str_contains($domain, $engineDomain)) {
                $keyword = $this->extractSearchKeyword($referer, $engineDomain);
                return [
                    'type' => 2,
                    'domain' => $domain,
                    'engine' => $engineName,
                    'keyword' => $keyword,
                ];
            }
        }

        // 外部链接
        return [
            'type' => 3,
            'domain' => $domain,
            'engine' => '',
            'keyword' => '',
        ];
    }

    /**
     * 提取搜索关键词
     */
    protected function extractSearchKeyword(string $referer, string $engineDomain): string
    {
        $param = self::SEARCH_QUERY_PARAMS[$engineDomain] ?? 'q';
        $parsed = parse_url($referer);
        if (!isset($parsed['query'])) {
            return '';
        }

        parse_str($parsed['query'], $params);
        $keyword = $params[$param] ?? '';

        if (!empty($keyword)) {
            $keyword = urldecode($keyword);
        }

        return mb_substr($keyword, 0, 255);
    }
}

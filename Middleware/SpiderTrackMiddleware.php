<?php

namespace App\Apps\CmsproSitestat\Middleware;

use App\Apps\CmsproSitestat\Services\SpiderService;
use App\Models\ConfigItem;
use Closure;
use Illuminate\Http\Request;

class SpiderTrackMiddleware
{
    public function __construct(
        protected SpiderService $spiderService
    ) {}

    /**
     * 蜘蛛访问检测与多层防护
     *
     * Layer0: 蜘蛛跟踪开关检测 → 关闭则不处理
     * Layer1: redirect 嵌套检测 → 404 迷惑
     * Layer2: 登录页面动作检测 → allow / block_404 / block_403
     * Layer3: 路径黑名单过滤
     * Layer4: 频率控制 → 429
     * Layer5: 蜘蛛黑名单 → 403
     * Layer6: 引导头 (X-Robots-Tag + Crawl-Delay)
     */
    public function handle(Request $request, Closure $next)
    {
        $ua = $request->userAgent() ?? '';

        // 快速排除：UA中不含爬虫特征关键词时不检测
        if (empty($ua) || !preg_match('/(?:bot|spider|crawler|fetcher|scraper|slurp)/i', $ua)) {
            return $next($request);
        }

        $spiderName = $this->spiderService->detectSpider($ua);
        if (empty($spiderName)) {
            return $next($request);
        }

        // Layer0: 跟踪开关
        $trackEnabled = ConfigItem::where('code', 'app_cmspro_sitestat_spider_track_enabled')->value('value');
        if ($trackEnabled !== '1') {
            return $next($request);
        }

        // Layer1: redirect 嵌套 >= 3 次 → 直接 404 迷惑蜘蛛
        $fullUrl = $request->fullUrl();
        if (substr_count($fullUrl, 'redirect=') >= 3) {
            return response('Not Found', 404);
        }

        // Layer2: 登录页面动作
        $path = $request->path();
        $isLoginPage = (bool) preg_match('/^(login|register|password)/', $path);
        if ($isLoginPage) {
            $loginAction = ConfigItem::where('code', 'app_cmspro_sitestat_login_page_action')->value('value') ?? 'allow';
            if ($loginAction === 'block_404') {
                return response('Not Found', 404);
            }
            if ($loginAction === 'block_403') {
                return response('Forbidden', 403);
            }
            // allow: 继续执行，只记录不拦截
        }

        // Layer3: 排除无 SEO 价值的路径
        if ($this->spiderService->isExcludedPath($path)) {
            return $next($request);
        }

        // Layer5: 蜘蛛黑名单 → 403
        $blockedStr = ConfigItem::where('code', 'app_cmspro_sitestat_blocked_spiders')->value('value') ?? '';
        $blockedSpiders = array_filter(array_map('trim', explode(',', $blockedStr)));
        if (in_array($spiderName, $blockedSpiders, true)) {
            return response('Forbidden', 403);
        }

        // Layer5.1: IP 黑名单 → 403
        if ($this->spiderService->isIpBlocked($request->ip())) {
            return response('Forbidden', 403);
        }

        // Layer5.2: UA 黑名单 → 403
        if ($this->spiderService->isUaBlocked($ua)) {
            return response('Forbidden', 403);
        }

        // Layer4: 频率控制 → 429
        if ($this->spiderService->isRateLimited($spiderName, $request->ip())) {
            return response('Too Many Requests', 429)
                ->header('Retry-After', '60');
        }

        // 记录蜘蛛访问
        $this->spiderService->collectFromRequest($request);

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        // Layer6: 引导头
        if (!$response->headers->has('X-Robots-Tag')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        $crawlDelay = ConfigItem::where('code', 'app_cmspro_sitestat_crawl_delay')->value('value') ?? '10';
        $response->headers->set('Crawl-Delay', (string) $crawlDelay);

        return $response;
    }
}

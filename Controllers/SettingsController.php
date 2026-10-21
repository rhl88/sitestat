<?php

namespace App\Apps\CmsproSitestat\Controllers;

use App\Models\ConfigGroup;
use App\Models\ConfigItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingsController extends Controller
{
    /**
     * 获取或创建应用配置分组
     */
    protected function ensureGroup(): ConfigGroup
    {
        return ConfigGroup::firstOrCreate(
            ['code' => 'app_cmspro_sitestat'],
            ['name' => '访问统计', 'app_id' => 'cmspro.sitestat', 'sort' => 0, 'status' => 1]
        );
    }

    public function index()
    {
        // 统计代码直接在服务端生成，随页面渲染输出，避免前端 AJAX 不稳定
        $sdkUrl = url('/apps/cmspro.sitestat/js/sitestat.js');
        $embedCode = '<script src="' . $sdkUrl . '"></script>';

        return view('cmspro.sitestat::settings', compact('embedCode'));
    }

    public function apiGetSettings()
    {
        $settings = [];
        $codes = [
            'track_mode' => 'app_cmspro_sitestat_track_mode',
            'sample_rate' => 'app_cmspro_sitestat_sample_rate',
            'exclude_paths' => 'app_cmspro_sitestat_exclude_paths',
            'data_retention_days' => 'app_cmspro_sitestat_data_retention_days',
            'enable_region_stat' => 'app_cmspro_sitestat_enable_region_stat',
            // 蜘蛛管理
            'spider_track_enabled' => 'app_cmspro_sitestat_spider_track_enabled',
            'login_page_action' => 'app_cmspro_sitestat_login_page_action',
            'crawl_delay' => 'app_cmspro_sitestat_crawl_delay',
            'custom_exclude_paths' => 'app_cmspro_sitestat_custom_exclude_paths',
            'blocked_spiders' => 'app_cmspro_sitestat_blocked_spiders',
        ];

        foreach ($codes as $key => $code) {
            $settings[$key] = ConfigItem::where('code', $code)->value('value') ?? '';
        }

        return response()->json(['code' => 0, 'data' => $settings]);
    }

    public function apiSaveSettings(Request $request)
    {
        $group = $this->ensureGroup();

        $fields = [
            'track_mode' => 'app_cmspro_sitestat_track_mode',
            'sample_rate' => 'app_cmspro_sitestat_sample_rate',
            'exclude_paths' => 'app_cmspro_sitestat_exclude_paths',
            'data_retention_days' => 'app_cmspro_sitestat_data_retention_days',
            'enable_region_stat' => 'app_cmspro_sitestat_enable_region_stat',
            // 蜘蛛管理
            'spider_track_enabled' => 'app_cmspro_sitestat_spider_track_enabled',
            'login_page_action' => 'app_cmspro_sitestat_login_page_action',
            'crawl_delay' => 'app_cmspro_sitestat_crawl_delay',
            'custom_exclude_paths' => 'app_cmspro_sitestat_custom_exclude_paths',
            'blocked_spiders' => 'app_cmspro_sitestat_blocked_spiders',
        ];

        foreach ($fields as $field => $code) {
            if ($request->has($field)) {
                ConfigItem::updateOrCreate(
                    ['code' => $code],
                    ['value' => $request->input($field), 'group_id' => $group->id, 'name' => $field, 'type' => 'text']
                );
            }
        }

        // 如果有蜘蛛相关字段变更，重新生成 robots.txt
        if ($request->hasAny(['crawl_delay', 'custom_exclude_paths', 'blocked_spiders', 'spider_track_enabled'])) {
            $this->syncRobotsTxt();
        }

        return response()->json(['code' => 0, 'message' => '保存成功']);
    }

    /**
     * 根据当前配置生成 robots.txt 并写入 public/robots.txt
     */
    protected function syncRobotsTxt(): void
    {
        $crawlDelay = ConfigItem::where('code', 'app_cmspro_sitestat_crawl_delay')->value('value') ?? '10';
        $customPathsStr = ConfigItem::where('code', 'app_cmspro_sitestat_custom_exclude_paths')->value('value') ?? '';
        $blockedSpidersStr = ConfigItem::where('code', 'app_cmspro_sitestat_blocked_spiders')->value('value') ?? '';

        $blockedSpiders = array_filter(array_map('trim', explode(',', $blockedSpidersStr)));

        // 蜘蛛 UA 关键词映射（与前端 SPIDERS 对应）
        $uaMap = [
            'Google' => 'Googlebot',
            '百度' => 'Baiduspider',
            'Bing' => 'bingbot',
            '搜狗' => 'Sogou web spider',
            '360搜索' => '360Spider',
            '宜搜' => 'YisouSpider',
            'Yandex' => 'YandexBot',
            'Amazon' => 'Amazonbot',
            'Apple' => 'Applebot',
            '今日头条' => 'Bytespider',
            'Semrush' => 'SemrushBot',
            'Ahrefs' => 'AhrefsBot',
            'Majestic' => 'MJ12bot',
            '华为花瓣' => 'PetalBot',
            'Claude' => 'ClaudeBot',
            'OpenAI' => 'GPTBot',
            'ChatGPT' => 'ChatGPT-User',
            'Common Crawl' => 'CCBot',
            'Perplexity' => 'PerplexityBot',
            'DuckDuckGo' => 'DuckDuckBot',
            'Facebook' => 'Facebot',
            'Twitter' => 'Twitterbot',
            'LinkedIn' => 'LinkedInBot',
            'Telegram' => 'TelegramBot',
        ];

        $lines = [];

        // 被屏蔽蜘蛛专用规则
        foreach ($blockedSpiders as $name) {
            $ua = $uaMap[$name] ?? $name;
            $lines[] = "User-agent: {$ua}";
            $lines[] = 'Disallow: /';
            $lines[] = 'Crawl-Delay: 30';
            $lines[] = '';
        }

        // 通用规则
        $lines[] = 'User-agent: *';

        $defaultDisallows = [
            '/admin/',
            '/api/',
            '/user/',
            '*/login',
            '*/register',
            '*/password/',
            '*/logout',
            '/*?redirect=',
            '/*?code=',
            '/*?state=',
        ];
        foreach ($defaultDisallows as $p) {
            $lines[] = "Disallow: {$p}";
        }

        // 自定义排除路径
        if ($customPathsStr) {
            foreach (explode("\n", $customPathsStr) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $lines[] = "Disallow: {$p}";
                }
            }
        }

        $lines[] = "Crawl-Delay: {$crawlDelay}";

        $content = implode("\n", $lines) . "\n";

        // 写入 public/robots.txt
        $filePath = public_path('robots.txt');
        try {
            file_put_contents($filePath, $content);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('robots.txt 文件写入失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

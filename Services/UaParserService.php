<?php

namespace App\Apps\CmsproSitestat\Services;

class UaParserService
{
    /**
     * 解析浏览器信息
     */
    public function parseBrowser(string $ua): string
    {
        if (empty($ua)) {
            return '未知';
        }

        $browsers = [
            'Edg/' => 'Edge',
            'Chrome/' => 'Chrome',
            'Firefox/' => 'Firefox',
            'Safari/' => 'Safari',
            'MSIE' => 'IE',
            'Trident/' => 'IE',
            'Opera' => 'Opera',
            'OPR/' => 'Opera',
            'Vivaldi/' => 'Vivaldi',
            'MicroMessenger/' => '微信',
            'QQBrowser/' => 'QQ浏览器',
            'UCBrowser/' => 'UC浏览器',
            'HuaweiBrowser/' => '华为浏览器',
            'MiuiBrowser/' => '小米浏览器',
            'SamsungBrowser/' => '三星浏览器',
            'Baidu' => '百度浏览器',
            'SogouMobileBrowser/' => '搜狗浏览器',
            'QHBrowser' => '360浏览器',
        ];

        foreach ($browsers as $pattern => $name) {
            if (stripos($ua, $pattern) !== false) {
                return $name;
            }
        }

        return '其他';
    }

    /**
     * 解析操作系统信息
     */
    public function parseOs(string $ua): string
    {
        if (empty($ua)) {
            return '未知';
        }

        $systems = [
            'Windows NT 10.0' => 'Windows 10/11',
            'Windows NT 6.3' => 'Windows 8.1',
            'Windows NT 6.2' => 'Windows 8',
            'Windows NT 6.1' => 'Windows 7',
            'Windows NT 6.0' => 'Windows Vista',
            'Windows NT 5.1' => 'Windows XP',
            'Windows' => 'Windows',
            'Mac OS X' => 'macOS',
            'iPhone OS' => 'iOS',
            'iPad' => 'iOS',
            'Android' => 'Android',
            'Linux' => 'Linux',
            'Ubuntu' => 'Ubuntu',
            'HarmonyOS' => 'HarmonyOS',
        ];

        foreach ($systems as $pattern => $name) {
            if (stripos($ua, $pattern) !== false) {
                return $name;
            }
        }

        return '其他';
    }

    /**
     * 解析设备类型
     */
    public function parseDeviceType(string $ua): string
    {
        if (empty($ua)) {
            return 'desktop';
        }

        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/iPad|Android(?!.*Mobile)|Tablet|Silk/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * 完整解析UA
     */
    public function parse(string $ua): array
    {
        return [
            'browser' => $this->parseBrowser($ua),
            'os' => $this->parseOs($ua),
            'device_type' => $this->parseDeviceType($ua),
        ];
    }

    /**
     * 已知蜘蛛UA标识映射
     * key: UA中的关键词（不区分大小写）, value: 蜘蛛名称
     */
    private const SPIDERS = [
        'Googlebot' => 'Google',
        'Baiduspider' => '百度',
        'bingbot' => 'Bing',
        'Sogou web spider' => '搜狗',
        'Sogou inst spider' => '搜狗',
        '360Spider' => '360搜索',
        'YisouSpider' => '宜搜',
        'YandexBot' => 'Yandex',
        'Yahoo! Slurp' => 'Yahoo',
        'DuckDuckBot' => 'DuckDuckGo',
        'Bytespider' => '今日头条',
        'SemrushBot' => 'Semrush',
        'AhrefsBot' => 'Ahrefs',
        'MJ12bot' => 'Majestic',
        'DotBot' => 'Moz',
        'PetalBot' => '华为花瓣',
        'Applebot' => 'Apple',
        'Facebot' => 'Facebook',
        'Twitterbot' => 'Twitter',
        'LinkedInBot' => 'LinkedIn',
        'Slackbot' => 'Slack',
        'Discordbot' => 'Discord',
        'TelegramBot' => 'Telegram',
        'MauiBot' => 'Maui',
        'Amazonbot' => 'Amazon',
        'ClaudeBot' => 'Claude',
        'GPTBot' => 'OpenAI',
        'ChatGPT-User' => 'ChatGPT',
        'CCBot' => 'Common Crawl',
        'PerplexityBot' => 'Perplexity',
        'YouBot' => 'You.com',
        'Cohere-AI' => 'Cohere',
        'ia_archiver' => 'Alexa',
        'rogerbot' => 'Moz',
    ];

    /**
     * 检测UA是否为蜘蛛/爬虫
     * 返回蜘蛛名称，非蜘蛛返回空字符串
     */
    public function detectSpider(string $ua): string
    {
        if (empty($ua)) {
            return '';
        }

        foreach (self::SPIDERS as $pattern => $name) {
            if (stripos($ua, $pattern) !== false) {
                return $name;
            }
        }

        // 通用爬虫特征匹配（bot/spider/crawler）
        if (preg_match('/(?:bot|spider|crawler|fetcher|scraper|slurp)/i', $ua)) {
            return '其他爬虫';
        }

        return '';
    }
}

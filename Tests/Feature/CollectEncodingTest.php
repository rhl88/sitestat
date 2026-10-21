<?php

namespace App\Apps\CmsproSitestat\Tests\Feature;

use App\Apps\CmsproSitestat\Models\Visit;
use App\Apps\CmsproSitestat\Tests\CmsproSitestatTestCase;

/**
 * 采集接口编码净化测试
 *
 * 背景：统计脚本嵌入 GBK 等编码页面时，sendBeacon 按页面文档编码上报，
 * title 等字段携带非 UTF-8 原始字节，直接写入 utf8mb4 表触发 MySQL 1366 错误，
 * 导致该次访问记录丢失（线上报错样本：\xD2\xD7\xB2\xA9... 即 GBK「易博平...」）。
 * 修复后服务端统一净化：非 UTF-8 输入转换为 UTF-8、清除非法字节、按字段长度截断。
 */
class CollectEncodingTest extends CmsproSitestatTestCase
{
    public function test_GBK编码页面上报的标题被转换为UTF8入库(): void
    {
        // 模拟 GBK 页面经 sendBeacon 上报的原始 GBK 字节（线上报错样本同场景）
        $gbkTitle = mb_convert_encoding('易博平台手机版', 'GBK', 'UTF-8');
        $this->assertFalse(mb_check_encoding($gbkTitle, 'UTF-8'), '前置校验：GBK字节串不应为合法UTF-8');

        $this->post('/api/sitestat/collect', [
            'sid' => 'ss_test_gbk',
            'url' => 'https://wap.example.com/',
            'path' => '/',
            'title' => $gbkTitle,
            'referer' => '',
            'ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0',
            'screen' => '1024x898',
        ])->assertOk();

        $visit = Visit::where('session_id', 'ss_test_gbk')->first();
        $this->assertNotNull($visit, 'GBK编码上报的访问记录应成功入库');
        $this->assertSame('易博平台手机版', $visit->title, 'GBK字节应被转换为UTF-8中文');
        $this->assertTrue(mb_check_encoding($visit->title, 'UTF-8'));
    }

    public function test_UTF8编码上报正常入库不乱码(): void
    {
        $this->post('/api/sitestat/collect', [
            'sid' => 'ss_test_utf8',
            'url' => 'https://www.example.com/page',
            'path' => '/page',
            'title' => '正常中文标题',
            'referer' => 'https://www.baidu.com/s?wd=%E6%B5%8B%E8%AF%95',
            'ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/150.0.0.0',
            'screen' => '1920x1080',
        ])->assertOk();

        $this->assertDatabaseHas('app_cmspro_sitestat_visits', [
            'session_id' => 'ss_test_utf8',
            'path' => '/page',
            'title' => '正常中文标题',
            'referer_type' => 2,
            'search_engine' => '百度',
            'search_keyword' => '测试',
        ]);
    }

    public function test_超长标题按字段上限截断后入库(): void
    {
        $longTitle = str_repeat('测', 300); // 超过title字段varchar(255)上限

        $this->post('/api/sitestat/collect', [
            'sid' => 'ss_test_long',
            'url' => 'https://www.example.com/long',
            'path' => '/long',
            'title' => $longTitle,
            'screen' => '1920x1080',
        ])->assertOk();

        $visit = Visit::where('session_id', 'ss_test_long')->first();
        $this->assertNotNull($visit, '超长标题访问记录应成功入库');
        $this->assertSame(255, mb_strlen($visit->title), '标题应截断为255个字符');
        $this->assertSame(mb_substr($longTitle, 0, 255), $visit->title);
    }

    public function test_非法字节输入兜底清洗后仍可入库(): void
    {
        // 无法完整转换为UTF-8的二进制垃圾字节（含GBK可转部分与非法控制字节）
        $garbage = "\xB1\xB1\x00\xFF\xFEgarbage";

        $this->post('/api/sitestat/collect', [
            'sid' => 'ss_test_garbage',
            'url' => 'https://www.example.com/x',
            'path' => '/x',
            'title' => $garbage,
            'screen' => '1920x1080',
        ])->assertOk();

        $this->assertDatabaseCount('app_cmspro_sitestat_visits', 1);
        $visit = Visit::where('session_id', 'ss_test_garbage')->first();
        $this->assertNotNull($visit);
        $this->assertTrue(mb_check_encoding($visit->title, 'UTF-8'), '清洗后的标题必须是合法UTF-8');
    }

    public function test_数组参数注入不导致异常(): void
    {
        $this->post('/api/sitestat/collect', [
            'sid' => 'ss_test_array',
            'path' => ['/injection'],
            'title' => ['/injection'],
            'screen' => '1920x1080',
        ])->assertOk();

        $this->assertDatabaseHas('app_cmspro_sitestat_visits', [
            'session_id' => 'ss_test_array',
            'path' => '',
            'title' => '',
        ]);
    }
}

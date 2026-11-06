<?php

namespace App\Apps\CmsproSitestat\Tests\Feature;

use App\Apps\CmsproIplocation\Services\IpLocationService;
use App\Apps\CmsproSitestat\Models\SpiderVisit;
use App\Apps\CmsproSitestat\Services\SpiderService;
use App\Apps\CmsproSitestat\Tests\CmsproSitestatTestCase;
use App\Models\AdminUser;

/**
 * 蜘蛛统计页「访问记录」Tab 的 IP 地区列测试
 *
 * 背景：访问记录从蜘蛛统计页拆分为独立 Tab，并在 IP 列右侧新增「地区」列，
 * 归属地由 cmspro.iplocation 的 IpLocationService 按当前页 IP 去重后实时解析。
 */
class SpiderVisitRegionTest extends CmsproSitestatTestCase
{
    /**
     * 构造可控的 IP 归属地服务替身，记录查询次数
     */
    private function fakeIpService(array $locations, bool $configured = true): IpLocationService
    {
        $fake = new class($locations, $configured) extends IpLocationService {
            public array $queried = [];

            public function __construct(private array $locations, private bool $configured) {}

            public function isConfigured(): bool
            {
                return $this->configured;
            }

            public function searchToArray(string $ip): array
            {
                $this->queried[] = $ip;

                return array_merge(
                    ['ip' => $ip, 'country' => '', 'region' => '', 'province' => '', 'city' => '', 'isp' => '', 'version' => 'IPv4'],
                    $this->locations[$ip] ?? []
                );
            }
        };

        $this->app->instance(IpLocationService::class, $fake);

        return $fake;
    }

    private function createVisit(string $ip, string $spider = 'Google', string $path = '/'): SpiderVisit
    {
        return SpiderVisit::create([
            'spider_name' => $spider,
            'ip' => $ip,
            'url' => 'https://www.example.com' . $path,
            'path' => $path,
            'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
            'status' => 1,
        ]);
    }

    private function service(): SpiderService
    {
        return $this->app->make(SpiderService::class);
    }

    public function test_访问记录返回IP对应地区文本(): void
    {
        $this->fakeIpService([
            '114.114.114.114' => ['country' => '中国', 'province' => '广东', 'city' => '深圳'],
        ]);
        $this->createVisit('114.114.114.114');

        $data = $this->service()->getSpiderVisitList(date('Y-m-d'), date('Y-m-d'));

        $this->assertCount(1, $data['list']);
        $this->assertSame('中国 广东 深圳', $data['list'][0]['region']);
    }

    public function test_省份与城市重复时地区文本去重(): void
    {
        // 直辖市场景：省=北京、市=北京市，拼接后不应出现「北京 北京市」
        $this->fakeIpService([
            '1.2.3.4' => ['country' => '中国', 'province' => '北京', 'city' => '北京市'],
        ]);
        $this->createVisit('1.2.3.4');

        $data = $this->service()->getSpiderVisitList(date('Y-m-d'), date('Y-m-d'));

        $this->assertSame('中国 北京市', $data['list'][0]['region']);
    }

    public function test_同一IP在多条记录中只解析一次(): void
    {
        $fake = $this->fakeIpService([
            '5.6.7.8' => ['country' => '中国', 'province' => '浙江', 'city' => '杭州'],
        ]);
        $this->createVisit('5.6.7.8', 'Google', '/a');
        $this->createVisit('5.6.7.8', 'Google', '/b');
        $this->createVisit('5.6.7.8', 'Bing', '/c');

        $data = $this->service()->getSpiderVisitList(date('Y-m-d'), date('Y-m-d'));

        $this->assertCount(3, $data['list'], '三条记录都应出现在列表中');
        $this->assertSame(['5.6.7.8'], $fake->queried, '同一 IP 应只查询一次');
        foreach ($data['list'] as $item) {
            $this->assertSame('中国 浙江 杭州', $item['region']);
        }
    }

    public function test_IP库未配置时地区为空字符串(): void
    {
        $fake = $this->fakeIpService([], false);
        $this->createVisit('9.9.9.9');

        $data = $this->service()->getSpiderVisitList(date('Y-m-d'), date('Y-m-d'));

        $this->assertSame('', $data['list'][0]['region'], 'IP 库未配置时应返回空字符串，由前端展示占位符');
        $this->assertSame([], $fake->queried, 'IP 库未配置时不应发起解析');
    }

    public function test_无归属地信息的IP地区为空字符串(): void
    {
        $this->fakeIpService([]);
        $this->createVisit('0.0.0.0');

        $data = $this->service()->getSpiderVisitList(date('Y-m-d'), date('Y-m-d'));

        $this->assertSame('', $data['list'][0]['region']);
    }

    public function test_蜘蛛统计页拆分为两个Tab且默认打开蜘蛛统计(): void
    {
        $admin = AdminUser::create([
            'username' => 'spider_tester',
            'password' => 'password',
            'name' => '测试管理员',
            'status' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/cmspro/sitestat/spider');

        $response->assertOk();
        $html = $response->getContent();

        // 两个 Tab 标题：蜘蛛统计（默认打开）+ 访问记录
        $this->assertStringContainsString(
            '<li class="layui-this">蜘蛛统计</li>',
            $html,
            '「蜘蛛统计」Tab 应默认打开'
        );
        $this->assertStringContainsString('<li>访问记录</li>', $html, '应存在独立的「访问记录」Tab');
        $this->assertStringNotContainsString('最近访问记录', $html, '旧卡片标题应已移除');

        // 访问记录表格包含「地区」列
        $this->assertStringContainsString("{field: 'region', title: '地区'", $html);
    }

    public function test_访问记录工具栏位于Tab标题行且默认隐藏(): void
    {
        $admin = AdminUser::create([
            'username' => 'spider_toolbar_tester',
            'password' => 'password',
            'name' => '测试管理员',
            'status' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/cmspro/sitestat/spider');
        $response->assertOk();
        $html = $response->getContent();

        $titlePos = strpos($html, '<ul class="layui-tab-title">');
        $toolbarPos = strpos($html, 'id="visitToolbar"');
        $contentPos = strpos($html, '<div class="layui-tab-content">');

        $this->assertNotFalse($titlePos);
        $this->assertNotFalse($toolbarPos, '应存在访问记录工具栏容器');
        $this->assertNotFalse($contentPos);

        // 工具栏位于 Tab 标题行之后、Tab 内容之前，即与标题同行显示
        $this->assertGreaterThan($titlePos, $toolbarPos, '工具栏应在 Tab 标题行之后');
        $this->assertLessThan($contentPos, $toolbarPos, '工具栏应在 Tab 内容之前（与标题同行）');

        // 下拉框与黑名单管理按钮归属于该工具栏
        $toolbarHtml = substr($html, $toolbarPos, $contentPos - $toolbarPos);
        $this->assertStringContainsString('id="spiderFilter"', $toolbarHtml, '「全部蜘蛛」下拉框应在工具栏内');
        $this->assertStringContainsString('id="btnBlockManage"', $toolbarHtml, '「黑名单管理」按钮应在工具栏内');

        // 默认停留在「蜘蛛统计」Tab，工具栏不带 is-show（CSS 中 display:none，切到访问记录才显示）
        $this->assertStringNotContainsString('class="spider-tab-toolbar is-show"', $html, '默认应隐藏访问记录工具栏');
        $this->assertStringContainsString(
            "$('#visitToolbar').toggleClass('is-show', isVisitTab)",
            $html,
            '切换 Tab 时应按当前 Tab 显隐工具栏'
        );
    }
}
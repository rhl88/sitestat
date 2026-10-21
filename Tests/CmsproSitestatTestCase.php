<?php

namespace App\Apps\CmsproSitestat\Tests;

use App\Apps\CmsproSitestat\Install;
use App\Apps\CmsproSitestat\ServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 访问统计应用 · 测试基类
 *
 * 说明：
 * 1. 应用迁移经 Install::install() 幂等建表并记录 migrations 表；
 * 2. 应用 ServiceProvider 仅对已安装应用注册，测试环境需手动注册以加载采集路由；
 * 3. 与 CmsproIplocationTestCase 保持相同的测试环境搭建方式。
 */
abstract class CmsproSitestatTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new Install())->install();
        $this->registerAppProvider();
    }

    /**
     * 手动注册应用 ServiceProvider（加载采集路由前缀 api/sitestat）。
     */
    protected function registerAppProvider(): void
    {
        $provider = $this->app->register(ServiceProvider::class);

        if ($provider && method_exists($provider, 'boot')) {
            $this->app->call([$provider, 'boot']);
        }
    }
}

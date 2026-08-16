<?php

namespace App\Apps\CmsproSitestat;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('app/Apps/CmsproSitestat/Config/sitestat.php'),
            'apps.cmspro.sitestat'
        );
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->loadViews();
        $this->registerSchedule();
        $this->registerMiddleware();
    }

    protected function registerRoutes(): void
    {
        // 后台管理路由
        Route::prefix('admin/cmspro/sitestat')
            ->namespace('App\Apps\CmsproSitestat\Controllers')
            ->middleware(['web', 'auth:admin'])
            ->group(base_path('app/Apps/CmsproSitestat/Routes/admin.php'));

        // 数据采集API路由（公开，无需认证）
        Route::prefix('api/sitestat')
            ->middleware(['throttle:120,1'])
            ->group(base_path('app/Apps/CmsproSitestat/Routes/api.php'));
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(
            base_path('app/Apps/CmsproSitestat/Views'),
            'cmspro.sitestat'
        );
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            // 每日00:10聚合统计
            $schedule->call(function () {
                app(Services\DailyAggregationService::class)->aggregate();
            })->dailyAt('00:10')->name('sitestat:daily-aggregate');

            // 每日03:00清理过期原始数据
            $schedule->call(function () {
                app(Services\StatService::class)->cleanExpiredVisits();
                app(Services\SpiderService::class)->cleanExpiredVisits();
            })->dailyAt('03:00')->name('sitestat:clean-expired');
        });
    }

    /**
     * 注册蜘蛛采集中间件到web路由组
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', Middleware\SpiderTrackMiddleware::class);
    }
}

<?php

namespace App\Apps\CmsproSitestat\Services;

use App\Apps\CmsproSitestat\Models\DailyDevice;
use App\Apps\CmsproSitestat\Models\DailyPage;
use App\Apps\CmsproSitestat\Models\DailyRegion;
use App\Apps\CmsproSitestat\Models\DailySource;
use App\Apps\CmsproSitestat\Models\DailyStat;
use App\Apps\CmsproSitestat\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyAggregationService
{
    /**
     * 执行日聚合（默认聚合昨天）
     */
    public function aggregate(?string $date = null): void
    {
        $statDate = $date ?? Carbon::yesterday()->toDateString();

        try {
            DB::beginTransaction();

            $this->aggregateDailyStat($statDate);
            $this->aggregateDailyPages($statDate);
            $this->aggregateDailyRegions($statDate);
            $this->aggregateDailySources($statDate);
            $this->aggregateDailyDevices($statDate);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('访问统计日聚合失败', [
                'date' => $statDate,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 聚合日统计汇总
     */
    protected function aggregateDailyStat(string $date): void
    {
        $pv = Visit::whereDate('create_time', $date)->count();
        $uv = Visit::whereDate('create_time', $date)->distinct('session_id')->count('session_id');
        $ipCount = Visit::whereDate('create_time', $date)->distinct('ip')->count('ip');

        // 新访客：session_id在当天首次出现（之前没有记录）
        $sessionIds = Visit::whereDate('create_time', $date)->distinct('session_id')->pluck('session_id');
        $newUv = 0;
        foreach ($sessionIds->chunk(500) as $chunk) {
            $existingSessions = Visit::whereDate('create_time', '<', $date)
                ->whereIn('session_id', $chunk)
                ->distinct('session_id')
                ->pluck('session_id')
                ->toArray();
            $newUv += $chunk->count() - count($existingSessions);
        }

        // 跳出率：只访问了一个页面的会话占比
        $singlePageSessions = Visit::whereDate('create_time', $date)
            ->select('session_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('session_id')
            ->having('cnt', '=', 1)
            ->get()
            ->count();
        $totalSessions = $sessionIds->count();
        $bounceRate = $totalSessions > 0 ? round(($singlePageSessions / $totalSessions) * 100, 2) : 0;

        DailyStat::updateOrCreate(
            ['stat_date' => $date],
            [
                'pv' => $pv,
                'uv' => $uv,
                'ip_count' => $ipCount,
                'new_uv' => $newUv,
                'bounce_rate' => $bounceRate,
                'avg_duration' => 0,
            ]
        );
    }

    /**
     * 聚合页面日统计
     */
    protected function aggregateDailyPages(string $date): void
    {
        $pages = Visit::whereDate('create_time', $date)
            ->select('path', 'title', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
            ->groupBy('path', 'title')
            ->get();

        foreach ($pages as $page) {
            DailyPage::updateOrCreate(
                ['stat_date' => $date, 'path' => $page->path],
                [
                    'title' => $page->title,
                    'pv' => $page->pv,
                    'uv' => $page->uv,
                ]
            );
        }
    }

    /**
     * 聚合地域日统计
     */
    protected function aggregateDailyRegions(string $date): void
    {
        $regions = Visit::whereDate('create_time', $date)
            ->select('country', 'province', 'city', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
            ->groupBy('country', 'province', 'city')
            ->get();

        foreach ($regions as $region) {
            DailyRegion::updateOrCreate(
                [
                    'stat_date' => $date,
                    'country' => $region->country ?? '',
                    'province' => $region->province ?? '',
                    'city' => $region->city ?? '',
                ],
                [
                    'pv' => $region->pv,
                    'uv' => $region->uv,
                ]
            );
        }
    }

    /**
     * 聚合来源日统计
     */
    protected function aggregateDailySources(string $date): void
    {
        $sources = Visit::whereDate('create_time', $date)
            ->select('referer_type', 'referer_domain', 'search_engine', 'search_keyword', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
            ->groupBy('referer_type', 'referer_domain', 'search_engine', 'search_keyword')
            ->get();

        foreach ($sources as $source) {
            DailySource::updateOrCreate(
                [
                    'stat_date' => $date,
                    'referer_type' => $source->referer_type,
                    'referer_domain' => $source->referer_domain,
                    'search_engine' => $source->search_engine,
                    'search_keyword' => $source->search_keyword,
                ],
                [
                    'pv' => $source->pv,
                    'uv' => $source->uv,
                ]
            );
        }
    }

    /**
     * 聚合设备日统计
     */
    protected function aggregateDailyDevices(string $date): void
    {
        $devices = Visit::whereDate('create_time', $date)
            ->select('browser', 'os', 'device_type', 'screen_resolution', DB::raw('COUNT(*) as pv'), DB::raw('COUNT(DISTINCT session_id) as uv'))
            ->groupBy('browser', 'os', 'device_type', 'screen_resolution')
            ->get();

        foreach ($devices as $device) {
            DailyDevice::updateOrCreate(
                [
                    'stat_date' => $date,
                    'browser' => $device->browser,
                    'os' => $device->os,
                    'device_type' => $device->device_type,
                    'screen_resolution' => $device->screen_resolution,
                ],
                [
                    'pv' => $device->pv,
                    'uv' => $device->uv,
                ]
            );
        }
    }
}

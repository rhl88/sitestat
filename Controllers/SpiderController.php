<?php

namespace App\Apps\CmsproSitestat\Controllers;

use App\Apps\CmsproSitestat\Services\SpiderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SpiderController extends Controller
{
    public function __construct(
        protected SpiderService $spiderService
    ) {}

    /**
     * 蜘蛛统计页面
     */
    public function index()
    {
        return view('cmspro.sitestat::spider');
    }

    /**
     * 蜘蛛统计概览API
     */
    public function apiSpider(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $data = $this->spiderService->getSpiderOverview($startDate, $endDate);

        return response()->json(['code' => 0, 'data' => $data]);
    }

    /**
     * 蜘蛛访问明细API
     */
    public function apiSpiderVisits(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('limit', 20);
        $spiderName = $request->input('spider_name');

        $data = $this->spiderService->getSpiderVisitList($startDate, $endDate, $page, $pageSize, $spiderName);

        return response()->json(['code' => 0, 'data' => $data]);
    }

    /**
     * 拉黑 IP 或 UA
     */
    public function apiBlock(Request $request)
    {
        $type = $request->input('type'); // ip | ua
        $value = trim((string) $request->input('value'));

        if (!in_array($type, ['ip', 'ua'], true) || $value === '') {
            return response()->json(['code' => 1, 'message' => '参数不合法']);
        }

        $added = $type === 'ip'
            ? $this->spiderService->blockIp($value)
            : $this->spiderService->blockUa($value);

        return response()->json([
            'code' => 0,
            'message' => $added ? '已加入黑名单' : '该值已在黑名单中',
        ]);
    }

    /**
     * 解除 IP 或 UA 黑名单
     */
    public function apiUnblock(Request $request)
    {
        $type = $request->input('type'); // ip | ua
        $value = trim((string) $request->input('value'));

        if (!in_array($type, ['ip', 'ua'], true) || $value === '') {
            return response()->json(['code' => 1, 'message' => '参数不合法']);
        }

        $removed = $this->spiderService->unblock($type, $value);

        return response()->json([
            'code' => 0,
            'message' => $removed ? '已解除黑名单' : '该值不在黑名单中',
        ]);
    }

    /**
     * 获取黑名单列表
     */
    public function apiBlockedList()
    {
        return response()->json([
            'code' => 0,
            'data' => [
                'ips' => $this->spiderService->getBlockedIps(),
                'uas' => $this->spiderService->getBlockedUas(),
            ],
        ]);
    }
}

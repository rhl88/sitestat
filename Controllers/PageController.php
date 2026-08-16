<?php

namespace App\Apps\CmsproSitestat\Controllers;

use App\Apps\CmsproSitestat\Services\StatService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function __construct(
        protected StatService $statService
    ) {}

    public function index()
    {
        return view('cmspro.sitestat::page');
    }

    public function apiPage(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('limit', 20);

        $data = $this->statService->getPageStats($startDate, $endDate, $page, $pageSize);

        return response()->json(['code' => 0, 'data' => $data]);
    }
}

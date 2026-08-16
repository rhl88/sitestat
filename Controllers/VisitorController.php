<?php

namespace App\Apps\CmsproSitestat\Controllers;

use App\Apps\CmsproSitestat\Services\StatService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VisitorController extends Controller
{
    public function __construct(
        protected StatService $statService
    ) {}

    public function index()
    {
        return view('cmspro.sitestat::visitor');
    }

    public function apiVisitor(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('limit', 20);

        $data = $this->statService->getVisitorList($startDate, $endDate, $page, $pageSize);

        return response()->json(['code' => 0, 'data' => $data]);
    }
}

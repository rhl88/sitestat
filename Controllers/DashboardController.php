<?php

namespace App\Apps\CmsproSitestat\Controllers;

use App\Apps\CmsproSitestat\Services\StatService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __construct(
        protected StatService $statService
    ) {}

    public function index()
    {
        return view('cmspro.sitestat::dashboard');
    }

    public function apiDashboard(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $data = $this->statService->getDashboard($startDate, $endDate);

        return response()->json(['code' => 0, 'data' => $data]);
    }
}

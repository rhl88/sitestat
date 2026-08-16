<?php

namespace App\Apps\CmsproSitestat\Controllers;

use App\Apps\CmsproSitestat\Services\StatService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SourceController extends Controller
{
    public function __construct(
        protected StatService $statService
    ) {}

    public function index()
    {
        return view('cmspro.sitestat::source');
    }

    public function apiSource(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $data = $this->statService->getSourceStats($startDate, $endDate);

        return response()->json(['code' => 0, 'data' => $data]);
    }
}

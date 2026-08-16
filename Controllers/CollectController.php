<?php

namespace App\Apps\CmsproSitestat\Controllers;

use App\Apps\CmsproSitestat\Services\CollectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CollectController
{
    public function __construct(
        protected CollectService $collectService
    ) {}

    /**
     * 接收采集数据
     */
    public function collect(Request $request)
    {
        $this->collectService->collect($request);

        // 返回1x1透明GIF图片（兼容传统统计方式）
        $gif = base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
        return Response::make($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}

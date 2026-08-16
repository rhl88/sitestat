<?php

use App\Apps\CmsproSitestat\Controllers\CollectController;
use Illuminate\Support\Facades\Route;

// 数据采集接口（公开，JS SDK调用）
Route::post('/collect', [CollectController::class, 'collect']);

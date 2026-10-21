<?php

use App\Apps\CmsproSitestat\Controllers\DashboardController;
use App\Apps\CmsproSitestat\Controllers\DeviceController;
use App\Apps\CmsproSitestat\Controllers\PageController;
use App\Apps\CmsproSitestat\Controllers\RegionController;
use App\Apps\CmsproSitestat\Controllers\SettingsController;
use App\Apps\CmsproSitestat\Controllers\SourceController;
use App\Apps\CmsproSitestat\Controllers\SpiderController;
use App\Apps\CmsproSitestat\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

// 视图路由
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/region', [RegionController::class, 'index']);
Route::get('/source', [SourceController::class, 'index']);
Route::get('/page', [PageController::class, 'index']);
Route::get('/device', [DeviceController::class, 'index']);
Route::get('/visitor', [VisitorController::class, 'index']);
Route::get('/spider', [SpiderController::class, 'index']);
Route::get('/settings', [SettingsController::class, 'index']);

// API路由
Route::prefix('api')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'apiDashboard']);
    Route::get('/region', [RegionController::class, 'apiRegion']);
    Route::get('/source', [SourceController::class, 'apiSource']);
    Route::get('/page', [PageController::class, 'apiPage']);
    Route::get('/device', [DeviceController::class, 'apiDevice']);
    Route::get('/visitor', [VisitorController::class, 'apiVisitor']);
    Route::get('/spider', [SpiderController::class, 'apiSpider']);
    Route::get('/spider/visits', [SpiderController::class, 'apiSpiderVisits']);
    Route::post('/spider/block', [SpiderController::class, 'apiBlock']);
    Route::post('/spider/unblock', [SpiderController::class, 'apiUnblock']);
    Route::get('/spider/blocked-list', [SpiderController::class, 'apiBlockedList']);
    Route::get('/settings', [SettingsController::class, 'apiGetSettings']);
    Route::post('/settings', [SettingsController::class, 'apiSaveSettings']);
});

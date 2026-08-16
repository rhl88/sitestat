<?php

namespace App\Apps\CmsproSitestat\Models;

use App\Models\BaseModel;

class DailySource extends BaseModel
{
    protected $table = 'app_cmspro_sitestat_daily_sources';

    public const CREATED_AT = 'create_time';

    public const UPDATED_AT = 'update_time';

    protected $fillable = [
        'stat_date',
        'referer_type',
        'referer_domain',
        'search_engine',
        'search_keyword',
        'pv',
        'uv',
        'status',
    ];

    protected $casts = [
        'stat_date' => 'date:Y-m-d',
        'referer_type' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime:Y-m-d H:i:s',
        'update_time' => 'datetime:Y-m-d H:i:s',
    ];
}

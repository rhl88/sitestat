<?php

namespace App\Apps\CmsproSitestat\Models;

use App\Models\BaseModel;

class DailyPage extends BaseModel
{
    protected $table = 'app_cmspro_sitestat_daily_pages';

    public const CREATED_AT = 'create_time';

    public const UPDATED_AT = 'update_time';

    protected $fillable = [
        'stat_date',
        'path',
        'title',
        'pv',
        'uv',
        'status',
    ];

    protected $casts = [
        'stat_date' => 'date:Y-m-d',
        'status' => 'integer',
        'create_time' => 'datetime:Y-m-d H:i:s',
        'update_time' => 'datetime:Y-m-d H:i:s',
    ];
}

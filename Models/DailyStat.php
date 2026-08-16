<?php

namespace App\Apps\CmsproSitestat\Models;

use App\Models\BaseModel;

class DailyStat extends BaseModel
{
    protected $table = 'app_cmspro_sitestat_daily_stats';

    public const CREATED_AT = 'create_time';

    public const UPDATED_AT = 'update_time';

    protected $fillable = [
        'stat_date',
        'pv',
        'uv',
        'ip_count',
        'new_uv',
        'bounce_rate',
        'avg_duration',
        'status',
    ];

    protected $casts = [
        'stat_date' => 'date:Y-m-d',
        'bounce_rate' => 'float',
        'status' => 'integer',
        'create_time' => 'datetime:Y-m-d H:i:s',
        'update_time' => 'datetime:Y-m-d H:i:s',
    ];
}

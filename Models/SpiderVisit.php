<?php

namespace App\Apps\CmsproSitestat\Models;

use App\Models\BaseModel;

class SpiderVisit extends BaseModel
{
    protected $table = 'app_cmspro_sitestat_spider_visits';

    public const CREATED_AT = 'create_time';

    public const UPDATED_AT = 'update_time';

    protected $fillable = [
        'spider_name',
        'ip',
        'url',
        'path',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'create_time' => 'datetime:Y-m-d H:i:s',
        'update_time' => 'datetime:Y-m-d H:i:s',
    ];
}

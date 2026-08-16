<?php

namespace App\Apps\CmsproSitestat\Models;

use App\Models\BaseModel;

class Visit extends BaseModel
{
    protected $table = 'app_cmspro_sitestat_visits';

    public const CREATED_AT = 'create_time';

    public const UPDATED_AT = 'update_time';

    protected $fillable = [
        'session_id',
        'ip',
        'country',
        'province',
        'city',
        'isp',
        'url',
        'path',
        'title',
        'referer',
        'referer_domain',
        'referer_type',
        'search_engine',
        'search_keyword',
        'user_agent',
        'browser',
        'os',
        'device_type',
        'screen_resolution',
        'status',
    ];

    protected $casts = [
        'referer_type' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime:Y-m-d H:i:s',
        'update_time' => 'datetime:Y-m-d H:i:s',
    ];
}

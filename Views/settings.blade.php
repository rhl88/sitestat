<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>统计设置 - 访问统计</title>
    <link rel="stylesheet" href="{{ asset('CmsProUi/component/pear/css/pear.css') }}">
    <link rel="stylesheet" href="{{ asset('CmsProUi/font-awesome/4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/reset.css') }}">
    <style>
        .blocked-tag {
            display: inline-block;
            background: #fff1f0;
            border: 1px solid #ffa39e;
            color: #cf1322;
            border-radius: 4px;
            padding: 2px 8px;
            margin: 0 8px 8px 0;
            font-size: 12px;
            line-height: 20px;
        }
        .blocked-tag .blocked-remove {
            color: #cf1322;
            margin-left: 6px;
            text-decoration: none;
        }
        .blocked-tag .blocked-remove:hover {
            color: #ff4d4f;
        }
    </style>
</head>
<body>
<div class="pear-container">
    <div class="layui-card">
        <div class="layui-card-header">JS SDK 接入</div>
        <div class="layui-card-body">
            <div style="margin-bottom:15px;">
                <p style="color:#666;margin-bottom:10px;">将以下代码添加到网站前端的页面中（建议放在 &lt;/head&gt; 标签前），即可开始采集访问数据。</p>
                <div class="layui-input-inline" style="width:100%;">
                    <textarea id="embedCode" class="layui-textarea" style="height:60px;background:#f8f8f8;resize:none;" readonly></textarea>
                </div>
                <button class="layui-btn layui-btn-sm" style="margin-top:8px;" id="btnCopy"><i class="fa fa-copy"></i> 复制代码</button>
            </div>
        </div>
    </div>

    <div class="layui-card" style="margin-top:15px;">
        <div class="layui-card-header">统计设置</div>
        <div class="layui-card-body">
            <form class="layui-form" lay-filter="settingsForm">
                <div class="layui-form-item">
                    <label class="layui-form-label">统计模式</label>
                    <div class="layui-input-block">
                        <select name="track_mode" id="trackMode" lay-filter="trackMode">
                            <option value="full">全量统计</option>
                            <option value="sample">抽样统计</option>
                        </select>
                        <div class="layui-form-mid layui-word-aux">全量统计记录所有访问，抽样统计仅记录部分访问以降低服务器压力</div>
                    </div>
                </div>

                <div class="layui-form-item" id="sampleRateRow" style="display:none;">
                    <label class="layui-form-label">抽样率</label>
                    <div class="layui-input-inline" style="width:120px;">
                        <input type="number" name="sample_rate" id="sampleRate" class="layui-input" min="1" max="100" value="10">
                    </div>
                    <div class="layui-form-mid layui-word-aux">取值1-100，表示记录百分之几的访问</div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">排除路径</label>
                    <div class="layui-input-block">
                        <textarea name="exclude_paths" id="excludePaths" class="layui-textarea" style="height:80px;" placeholder="不统计的页面路径，每行一个，支持通配符&#10;例如：/admin/*&#10;/api/*"></textarea>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">数据保留</label>
                    <div class="layui-input-inline" style="width:120px;">
                        <input type="number" name="data_retention_days" id="dataRetentionDays" class="layui-input" min="0" value="90">
                    </div>
                    <div class="layui-form-mid layui-word-aux">天，超过保留天数的原始访问记录将自动清理，设为0则不自动清理</div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">地域统计</label>
                    <div class="layui-input-block">
                        <input type="checkbox" name="enable_region_stat" id="enableRegionStat" lay-skin="switch" lay-text="开启|关闭" checked>
                        <div class="layui-form-mid layui-word-aux">开启后将解析访客IP归属地，需要IP归属地应用已配置</div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn" lay-submit lay-filter="saveSettings"><i class="fa fa-save"></i> 保存设置</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="layui-card" style="margin-top:15px;">
        <div class="layui-card-header">蜘蛛管理</div>
        <div class="layui-card-body">
            <form class="layui-form" lay-filter="spiderForm">
                <div class="layui-form-item">
                    <label class="layui-form-label">蜘蛛跟踪</label>
                    <div class="layui-input-block">
                        <input type="checkbox" name="spider_track_enabled" id="spiderTrackEnabled" lay-skin="switch" lay-text="开启|关闭" value="1">
                        <div class="layui-form-mid layui-word-aux">开启后系统会记录蜘蛛访问数据并在中间件中执行拦截规则</div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">登录页面</label>
                    <div class="layui-input-block">
                        <select name="login_page_action" id="loginPageAction">
                            <option value="allow">允许访问（仅记录不拦截）</option>
                            <option value="block_404">返回 404 Not Found（迷惑蜘蛛）</option>
                            <option value="block_403">返回 403 Forbidden（禁止访问）</option>
                        </select>
                        <div class="layui-form-mid layui-word-aux">蜘蛛访问 /login /register /password 等页面时的处理方式</div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">Crawl-Delay</label>
                    <div class="layui-input-inline" style="width:120px;">
                        <input type="number" name="crawl_delay" id="crawlDelay" class="layui-input" min="1" max="999" value="10">
                    </div>
                    <div class="layui-form-mid layui-word-aux">秒，响应头中告知蜘蛛的抓取间隔</div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">自定义排除路径</label>
                    <div class="layui-input-block">
                        <textarea name="custom_exclude_paths" id="customExcludePaths" class="layui-textarea" style="height:80px;" placeholder="每行一条，支持通配符 *&#10;例如：/search*&#10;/sitemap*"></textarea>
                        <div class="layui-form-mid layui-word-aux">添加到 robots.txt 的 Disallow 规则，每行一条</div>
                    </div>
                </div>

                <div class="layui-form-item layui-form-text">
                    <label class="layui-form-label">屏蔽蜘蛛</label>
                    <div class="layui-input-block" id="spiderCheckboxList" style="padding-top:6px;">
                        <div style="color:#999;margin-bottom:10px;">勾选的蜘蛛将被 robots.txt 禁止抓取全站，且中间件直接返回 403</div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn" lay-submit lay-filter="saveSpider"><i class="fa fa-save"></i> 保存设置</button>
                        <button class="layui-btn layui-btn-primary" id="btnPreviewRobots" type="button"><i class="fa fa-eye"></i> 预览 robots.txt</button>
                    </div>
                </div>
            </form>
        </div>
    <div class="layui-card" style="margin-top:15px;">
        <div class="layui-card-header">黑名单管理</div>
        <div class="layui-card-body">
            <div class="layui-form-item">
                <label class="layui-form-label">IP 黑名单</label>
                <div class="layui-input-block" id="blockedIpList" style="padding-top:6px;">
                    <div style="color:#999;">暂无拉黑的 IP</div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">UA 黑名单</label>
                <div class="layui-input-block" id="blockedUaList" style="padding-top:6px;">
                    <div style="color:#999;">暂无拉黑的 UA</div>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <div style="color:#999;">被拉黑的 IP/UA 的蜘蛛访问将被中间件直接返回 403 拦截。可在蜘蛛统计页的最近访问记录中快速拉黑，也可在此处解除。</div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="robotsPreviewDialog" style="display:none;">
    <pre id="robotsPreviewContent" style="padding:15px;margin:0;background:#f8f8f8;border:1px solid #e6e6e6;border-radius:4px;font-family:Consolas,monospace;font-size:13px;line-height:1.8;white-space:pre-wrap;max-height:400px;overflow-y:auto;"></pre>
</div>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script>
layui.use(['form', 'layer', 'jquery'], function(){
    var form = layui.form;
    var layer = layui.layer;
    var $ = layui.jquery;

    // 已知蜘蛛列表
    var SPIDERS = [
        {ua: 'Googlebot', name: 'Google'},
        {ua: 'Baiduspider', name: '百度'},
        {ua: 'bingbot', name: 'Bing'},
        {ua: 'Sogou web spider', name: '搜狗'},
        {ua: '360Spider', name: '360搜索'},
        {ua: 'YisouSpider', name: '宜搜'},
        {ua: 'YandexBot', name: 'Yandex'},
        {ua: 'Amazonbot', name: 'Amazon'},
        {ua: 'Applebot', name: 'Apple'},
        {ua: 'Bytespider', name: '今日头条'},
        {ua: 'SemrushBot', name: 'Semrush'},
        {ua: 'AhrefsBot', name: 'Ahrefs'},
        {ua: 'MJ12bot', name: 'Majestic'},
        {ua: 'PetalBot', name: '华为花瓣'},
        {ua: 'ClaudeBot', name: 'Claude'},
        {ua: 'GPTBot', name: 'OpenAI'},
        {ua: 'ChatGPT-User', name: 'ChatGPT'},
        {ua: 'CCBot', name: 'Common Crawl'},
        {ua: 'PerplexityBot', name: 'Perplexity'},
        {ua: 'DuckDuckBot', name: 'DuckDuckGo'},
        {ua: 'Facebot', name: 'Facebook'},
        {ua: 'Twitterbot', name: 'Twitter'},
        {ua: 'LinkedInBot', name: 'LinkedIn'},
        {ua: 'TelegramBot', name: 'Telegram'},
    ];

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    // 生成蜘蛛复选框
    function renderSpiderCheckboxes(blockedList) {
        var html = '';
        SPIDERS.forEach(function(s) {
            var checked = blockedList.indexOf(s.name) !== -1 ? 'checked' : '';
            html += '<input type="checkbox" name="blocked_spiders" lay-skin="primary" title="' + s.name + '" value="' + s.name + '" ' + checked + '>';
        });
        $('#spiderCheckboxList').append(html);
        form.render('checkbox');
    }

    // 加载JS SDK地址
    $.get('/admin/cmspro/sitestat/api/js-sdk-url', function(res) {
        if (res.code === 0) {
            $('#embedCode').val(res.data.embed_code);
        }
    });

    // 加载设置
    $.get('/admin/cmspro/sitestat/api/settings', function(res) {
        if (res.code === 0) {
            var d = res.data;
            $('#trackMode').val(d.track_mode || 'full');
            $('#sampleRate').val(d.sample_rate || 10);
            $('#excludePaths').val(d.exclude_paths || '');
            $('#dataRetentionDays').val(d.data_retention_days || 90);

            if (d.enable_region_stat === '1') {
                $('#enableRegionStat').prop('checked', true);
            } else {
                $('#enableRegionStat').prop('checked', false);
            }

            if (d.track_mode === 'sample') {
                $('#sampleRateRow').show();
            }

            // 蜘蛛管理
            if (d.spider_track_enabled === '1') {
                $('#spiderTrackEnabled').prop('checked', true);
            } else {
                $('#spiderTrackEnabled').prop('checked', false);
            }
            $('#loginPageAction').val(d.login_page_action || 'allow');
            $('#crawlDelay').val(d.crawl_delay || 10);
            $('#customExcludePaths').val(d.custom_exclude_paths || '');

            var blocked = d.blocked_spiders || '';
            renderSpiderCheckboxes(blocked ? blocked.split(',') : []);

            form.render();
        }
    });

    // 统计模式切换
    form.on('select(trackMode)', function(data) {
        if (data.value === 'sample') {
            $('#sampleRateRow').show();
        } else {
            $('#sampleRateRow').hide();
        }
    });

    // 保存设置
    form.on('submit(saveSettings)', function(data) {
        var field = data.field;
        field.enable_region_stat = $('#enableRegionStat').is(':checked') ? '1' : '0';

        $.post('/admin/cmspro/sitestat/api/settings', field, function(res) {
            if (res.code === 0) {
                layer.msg('保存成功', {icon: 1});
            } else {
                layer.msg(res.message || '保存失败', {icon: 2});
            }
        });
        return false;
    });

    // 保存蜘蛛设置
    form.on('submit(saveSpider)', function(data) {
        var field = data.field;

        // 合并复选框值
        var checkedSpiders = [];
        $('input[name="blocked_spiders"]:checked').each(function(){
            checkedSpiders.push($(this).val());
        });
        field.blocked_spiders = checkedSpiders.join(',');

        // 开关转为 1/0
        field.spider_track_enabled = $('#spiderTrackEnabled').is(':checked') ? '1' : '0';

        $.post('/admin/cmspro/sitestat/api/settings', field, function(res) {
            if (res.code === 0) {
                layer.msg('保存成功，已同步更新 /robots.txt', {icon: 1});
            } else {
                layer.msg(res.message || '保存失败', {icon: 2});
            }
        });
        return false;
    });

    // 预览 robots.txt
    $('#btnPreviewRobots').on('click', function() {
        var lines = [];
        var crawlDelay = $('#crawlDelay').val() || 10;

        // 生成各蜘蛛规则
        var blocked = [];
        $('input[name="blocked_spiders"]:checked').each(function(){
            blocked.push($(this).val());
        });

        // 找被屏蔽蜘蛛的 UA
        var blockedUA = [];
        SPIDERS.forEach(function(s) {
            if (blocked.indexOf(s.name) !== -1) {
                blockedUA.push(s.ua);
            }
        });

        if (blockedUA.length > 0) {
            blockedUA.forEach(function(ua) {
                lines.push('User-agent: ' + ua);
                lines.push('Disallow: /');
                lines.push('Crawl-Delay: 30');
                lines.push('');
            });
        }

        lines.push('User-agent: *');
        var defaultDisallows = [
            '/admin/',
            '/api/',
            '/user/',
            '*/login',
            '*/register',
            '*/password/',
            '*/logout',
            '/*?redirect=',
            '/*?code=',
            '/*?state=',
        ];
        defaultDisallows.forEach(function(p) {
            lines.push('Disallow: ' + p);
        });

        // 自定义排除路径
        var customPaths = $('#customExcludePaths').val();
        if (customPaths) {
            customPaths.split('\n').forEach(function(p) {
                p = p.trim();
                if (p) {
                    lines.push('Disallow: ' + p);
                }
            });
        }

        lines.push('Crawl-Delay: ' + crawlDelay);

        layer.open({
            type: 1,
            title: 'robots.txt 预览',
            area: ['700px', '500px'],
            shadeClose: true,
            content: '<pre style="padding:15px;margin:0;background:#f8f8f8;font-family:Consolas,monospace;font-size:13px;line-height:1.8;white-space:pre-wrap;max-height:450px;overflow-y:auto;">' + lines.join('\n') + '</pre>'
        });
    });

    // 复制代码
    $('#btnCopy').on('click', function() {
        var code = $('#embedCode').val();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function() {
                layer.msg('已复制到剪贴板', {icon: 1});
            });
        } else {
            $('#embedCode').select();
            document.execCommand('copy');
            layer.msg('已复制到剪贴板', {icon: 1});
        }
    });

    // 渲染黑名单列表
    function renderBlockedList(container, items, type) {
        var $container = $(container);
        if (!items || items.length === 0) {
            $container.html('<div style="color:#999;">暂无拉黑的' + (type === 'ip' ? ' IP' : ' UA') + '</div>');
            return;
        }
        var html = '';
        items.forEach(function(item) {
            html += '<span class="blocked-tag" data-type="' + type + '" data-value="' + item + '">'
                + '<span class="blocked-text">' + item + '</span>'
                + '<a class="blocked-remove" href="javascript:;" title="解除拉黑"><i class="fa fa-times"></i></a>'
                + '</span>';
        });
        $container.html(html);
    }

    // 加载黑名单
    function loadBlockedList() {
        $.get('/admin/cmspro/sitestat/api/spider/blocked-list', function(res) {
            if (res.code === 0) {
                renderBlockedList('#blockedIpList', res.data.ips, 'ip');
                renderBlockedList('#blockedUaList', res.data.uas, 'ua');
            }
        });
    }

    // 解除拉黑（事件委托）
    $(document).on('click', '.blocked-remove', function() {
        var type = $(this).closest('.blocked-tag').data('type');
        var value = $(this).closest('.blocked-tag').data('value');
        var label = type === 'ip' ? 'IP' : 'UA';

        layer.confirm('确定解除' + label + '「' + value + '」的黑名单吗？', {
            icon: 3,
            title: '解除确认'
        }, function(index) {
            $.post('/admin/cmspro/sitestat/api/spider/unblock', {
                type: type,
                value: value
            }, function(res) {
                if (res.code === 0) {
                    layer.msg(res.message, {icon: 1});
                    loadBlockedList();
                } else {
                    layer.msg(res.message || '操作失败', {icon: 2});
                }
            });
            layer.close(index);
        });
    });

    loadBlockedList();
});

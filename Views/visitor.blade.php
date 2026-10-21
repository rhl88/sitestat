<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>访客明细 - 访问统计</title>
    <link rel="stylesheet" href="{{ asset('CmsProUi/component/pear/css/pear.css') }}">
    <link rel="stylesheet" href="{{ asset('CmsProUi/font-awesome/4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/reset.css') }}">
</head>
<body>
<div class="pear-container">
    <div class="layui-card">
        <div class="layui-card-header">
            <span>访客明细</span>
            <div class="layui-inline" style="float:right;">
                <div class="layui-input-inline" style="width:130px;">
                    <input type="text" id="startDate" placeholder="开始日期" class="layui-input">
                </div>
                <div class="layui-input-inline" style="width:130px;">
                    <input type="text" id="endDate" placeholder="结束日期" class="layui-input">
                </div>
                <button class="layui-btn layui-btn-sm" id="btnQuery"><i class="fa fa-search"></i> 查询</button>
            </div>
        </div>
        <div class="layui-card-body">
            <table id="visitorTable" lay-filter="visitorTable"></table>
        </div>
    </div>
</div>

<script type="text/html" id="urlTpl">
@verbatim
{{# if(d.url){ }}
<a href="{{ d.url }}" target="_blank" style="color:#1e9fff;">{{ d.url }}</a>
{{# } else { }}
{{ d.path }}
{{# } }}
@endverbatim
</script>

<script type="text/html" id="refererTypeTpl">
@verbatim
{{# if(d.referer_type === 1){ }}
<span class="layui-badge layui-bg-gray">直接访问</span>
{{# } else if(d.referer_type === 2 && d.referer){ }}
<a href="{{ d.referer }}" target="_blank" style="color:#1e9fff;"><span class="layui-badge layui-bg-blue">搜索引擎</span></a>
{{# } else if(d.referer_type === 2){ }}
<span class="layui-badge layui-bg-blue">搜索引擎</span>
{{# } else if(d.referer) { }}
<a href="{{ d.referer }}" target="_blank" style="color:#1e9fff;"><span class="layui-badge layui-bg-green">外部链接</span></a>
{{# } else { }}
<span class="layui-badge layui-bg-green">外部链接</span>
{{# } }}
@endverbatim
</script>

<script type="text/html" id="deviceTypeTpl">
@verbatim
{{# if(d.device_type === 'mobile'){ }}
<span class="layui-badge layui-bg-orange">移动端</span>
{{# } else if(d.device_type === 'tablet'){ }}
<span class="layui-badge layui-bg-cyan">平板</span>
{{# } else { }}
<span class="layui-badge">桌面端</span>
{{# } }}
@endverbatim
</script>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script>
layui.use(['table', 'laydate', 'jquery'], function(){
    var table = layui.table;
    var laydate = layui.laydate;
    var $ = layui.jquery;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    laydate.render({ elem: '#startDate', value: new Date(new Date().getTime() - 7*24*3600*1000) });
    laydate.render({ elem: '#endDate', value: new Date() });

    table.render({
        elem: '#visitorTable',
        url: '/admin/cmspro/sitestat/api/visitor',
        where: {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        },
        page: true,
        limits: [10, 20, 50, 100],
        limit: 20,
        cols: [[
            {field: 'ip', title: 'IP', width: 130},
            {field: 'country', title: '国家', width: 100},
            {field: 'province', title: '省份', width: 100},
            {field: 'city', title: '城市', width: 100},
            {field: 'path', title: '页面', minWidth: 180, templet: '#urlTpl'},
            {field: 'referer_type', title: '来源', width: 100, templet: '#refererTypeTpl'},
            {field: 'browser', title: '浏览器', width: 100},
            {field: 'os', title: '系统', width: 110},
            {field: 'device_type', title: '设备', width: 80, templet: '#deviceTypeTpl'},
            {field: 'create_time', title: '访问时间', width: 170}
        ]],
        parseData: function(res) {
            return {
                code: res.code,
                msg: '',
                count: res.data.total,
                data: res.data.list
            };
        }
    });

    function loadData() {
        table.reload('visitorTable', {
            where: {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val()
            },
            page: { curr: 1 }
        });
    }

    loadData();
    $('#btnQuery').on('click', loadData);
});
</script>
</body>
</html>

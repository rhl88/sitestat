<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>页面统计 - 访问统计</title>
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
            <span>页面统计</span>
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
            <table id="pageTable" lay-filter="pageTable"></table>
        </div>
    </div>
</div>

<script type="text/html" id="urlTpl">
@verbatim
{{# if(d.url){ }}
<a href="{{ d.url }}" target="_blank" style="color:#1e9fff;">{{ d.url }}</a>
{{# } else if(d.path){ }}
<a href="{{ d.path }}" target="_blank" style="color:#1e9fff;">{{ d.path }}</a>
{{# } else { }}
-
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

    laydate.render({ elem: '#startDate', value: new Date(new Date().getTime() - 30*24*3600*1000) });
    laydate.render({ elem: '#endDate', value: new Date() });

    table.render({
        elem: '#pageTable',
        url: '/admin/cmspro/sitestat/api/page',
        where: {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        },
        page: true,
        limits: [10, 20, 50, 100],
        limit: 20,
        cols: [[
            {type: 'numbers', title: '#', width: 60},
            {field: 'path', title: '页面路径', templet: '#urlTpl'},
            {field: 'title', title: '页面标题'},
            {field: 'pv', title: 'PV', width: 100, sort: true},
            {field: 'uv', title: 'UV', width: 100, sort: true}
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

    function loadTable() {
        table.reload('pageTable', {
            where: {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val()
            },
            page: { curr: 1 }
        });
    }

    loadTable();
    $('#btnQuery').on('click', loadTable);
});
</script>
</body>
</html>

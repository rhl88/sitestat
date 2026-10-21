<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>访客地域 - 访问统计</title>
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
            <span>访客地域</span>
            <div class="layui-inline" style="float:right;">
                <div class="layui-input-inline" style="width:130px;">
                    <input type="text" id="startDate" placeholder="开始日期" class="layui-input">
                </div>
                <div class="layui-input-inline" style="width:130px;">
                    <input type="text" id="endDate" placeholder="结束日期" class="layui-input">
                </div>
                <div class="layui-input-inline" style="width:120px;">
                    <select id="groupBy">
                        <option value="country">按国家</option>
                        <option value="province">按省份</option>
                        <option value="city">按城市</option>
                    </select>
                </div>
                <button class="layui-btn layui-btn-sm" id="btnQuery"><i class="fa fa-search"></i> 查询</button>
            </div>
        </div>
        <div class="layui-card-body">
            <table id="regionTable" lay-filter="regionTable"></table>
        </div>
    </div>
</div>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script>
layui.use(['table', 'laydate', 'jquery', 'form'], function(){
    var table = layui.table;
    var laydate = layui.laydate;
    var $ = layui.jquery;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    laydate.render({ elem: '#startDate', value: new Date(new Date().getTime() - 30*24*3600*1000) });
    laydate.render({ elem: '#endDate', value: new Date() });

    function getColumns() {
        var groupBy = $('#groupBy').val();
        if (groupBy === 'country') {
            return [[
                {field: 'country', title: '国家'},
                {field: 'pv', title: 'PV', sort: true},
                {field: 'uv', title: 'UV', sort: true}
            ]];
        }
        if (groupBy === 'city') {
            return [[
                {field: 'country', title: '国家'},
                {field: 'province', title: '省份'},
                {field: 'city', title: '城市'},
                {field: 'pv', title: 'PV', sort: true},
                {field: 'uv', title: 'UV', sort: true}
            ]];
        }
        return [[
            {field: 'country', title: '国家'},
            {field: 'province', title: '省份'},
            {field: 'pv', title: 'PV', sort: true},
            {field: 'uv', title: 'UV', sort: true}
        ]];
    }

    table.render({
        elem: '#regionTable',
        url: '/admin/cmspro/sitestat/api/region',
        where: {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            group_by: $('#groupBy').val()
        },
        page: true,
        limits: [10, 20, 50, 100],
        limit: 20,
        cols: getColumns(),
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
        var groupBy = $('#groupBy').val();
        table.reload('regionTable', {
            where: {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                group_by: groupBy
            },
            cols: getColumns(),
            page: { curr: 1 }
        });
    }

    loadTable();

    $('#btnQuery').on('click', loadTable);
    $('#groupBy').on('change', loadTable);
});
</script>
</body>
</html>

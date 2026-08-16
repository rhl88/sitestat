<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>设备分析 - 访问统计</title>
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
            <span>设备分析</span>
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
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md6">
                    <div class="layui-card">
                        <div class="layui-card-header">浏览器分布</div>
                        <div class="layui-card-body">
                            <div id="browserChart" style="height:300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="layui-col-md6">
                    <div class="layui-card">
                        <div class="layui-card-header">操作系统分布</div>
                        <div class="layui-card-body">
                            <div id="osChart" style="height:300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-row layui-col-space15" style="margin-top:15px;">
                <div class="layui-col-md6">
                    <div class="layui-card">
                        <div class="layui-card-header">设备类型分布</div>
                        <div class="layui-card-body">
                            <div id="deviceTypeChart" style="height:280px;"></div>
                        </div>
                    </div>
                </div>
                <div class="layui-col-md6">
                    <div class="layui-card">
                        <div class="layui-card-header">屏幕分辨率排行</div>
                        <div class="layui-card-body">
                            <table id="resolutionTable" lay-filter="resolutionTable"></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script src="{{ asset('apps/cmspro.sitestat/js/echarts.min.js') }}"></script>
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

    var browserChart = echarts.init(document.getElementById('browserChart'));
    var osChart = echarts.init(document.getElementById('osChart'));
    var deviceTypeChart = echarts.init(document.getElementById('deviceTypeChart'));

    var deviceTypeNames = {desktop: '桌面端', mobile: '移动端', tablet: '平板'};

    function loadData() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();

        $.get('/admin/cmspro/sitestat/api/device', {
            start_date: startDate,
            end_date: endDate
        }, function(res) {
            if (res.code === 0) {
                var d = res.data;

                // 浏览器饼图
                browserChart.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                    series: [{
                        type: 'pie',
                        radius: ['35%', '65%'],
                        data: d.browsers.map(function(i){ return {name: i.browser, value: i.pv}; }),
                        emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.5)' } }
                    }]
                });

                // OS饼图
                osChart.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                    series: [{
                        type: 'pie',
                        radius: ['35%', '65%'],
                        data: d.os.map(function(i){ return {name: i.os, value: i.pv}; }),
                        emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.5)' } }
                    }]
                });

                // 设备类型饼图
                deviceTypeChart.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                    series: [{
                        type: 'pie',
                        radius: ['35%', '65%'],
                        data: d.device_types.map(function(i){ return {name: deviceTypeNames[i.device_type] || i.device_type, value: i.pv}; }),
                        emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.5)' } }
                    }]
                });

                // 分辨率表格
                table.render({
                    elem: '#resolutionTable',
                    data: d.resolutions,
                    cols: [[
                        {field: 'screen_resolution', title: '分辨率', width: 150},
                        {field: 'pv', title: 'PV', sort: true},
                        {field: 'uv', title: 'UV', sort: true}
                    ]],
                    page: false,
                    limit: 10
                });
            }
        });
    }

    loadData();
    $('#btnQuery').on('click', loadData);
    window.addEventListener('resize', function(){
        browserChart.resize();
        osChart.resize();
        deviceTypeChart.resize();
    });
});
</script>
</body>
</html>

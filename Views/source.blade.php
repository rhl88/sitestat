<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>来源分析 - 访问统计</title>
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
            <span>来源分析</span>
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
                <div class="layui-col-md4">
                    <div class="layui-card">
                        <div class="layui-card-header">来源类型分布</div>
                        <div class="layui-card-body">
                            <div id="typeChart" style="height:280px;"></div>
                        </div>
                    </div>
                </div>
                <div class="layui-col-md4">
                    <div class="layui-card">
                        <div class="layui-card-header">搜索引擎排行</div>
                        <div class="layui-card-body">
                            <table id="engineTable" lay-filter="engineTable"></table>
                        </div>
                    </div>
                </div>
                <div class="layui-col-md4">
                    <div class="layui-card">
                        <div class="layui-card-header">搜索关键词排行</div>
                        <div class="layui-card-body">
                            <table id="keywordTable" lay-filter="keywordTable"></table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-card" style="margin-top:15px;">
                <div class="layui-card-header">外部链接排行</div>
                <div class="layui-card-body">
                    <table id="linkTable" lay-filter="linkTable"></table>
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

    var typeChart = echarts.init(document.getElementById('typeChart'));
    var typeNames = {1: '直接访问', 2: '搜索引擎', 3: '外部链接'};

    function loadData() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();

        $.get('/admin/cmspro/sitestat/api/source', {
            start_date: startDate,
            end_date: endDate
        }, function(res) {
            if (res.code === 0) {
                var d = res.data;

                // 来源类型饼图
                var pieData = d.by_type.map(function(i){
                    return { name: typeNames[i.referer_type] || '未知', value: i.pv };
                });
                typeChart.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                    series: [{
                        type: 'pie',
                        radius: ['40%', '70%'],
                        data: pieData,
                        emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.5)' } }
                    }]
                });

                // 搜索引擎排行（默认显示6条，超出分页）
                table.render({
                    elem: '#engineTable',
                    data: d.engines,
                    cols: [[
                        {field: 'search_engine', title: '搜索引擎'},
                        {field: 'pv', title: 'PV', sort: true},
                        {field: 'uv', title: 'UV', sort: true}
                    ]],
                    page: true,
                    limit: 6
                });

                // 搜索关键词排行（默认显示6条，超出分页）
                table.render({
                    elem: '#keywordTable',
                    data: d.keywords,
                    cols: [[
                        {field: 'search_keyword', title: '关键词'},
                        {field: 'search_engine', title: '引擎', width: 80},
                        {field: 'pv', title: 'PV', width: 70, sort: true}
                    ]],
                    page: true,
                    limit: 6
                });

                // 外部链接排行
                table.render({
                    elem: '#linkTable',
                    data: d.external_links,
                    cols: [[
                        {field: 'referer_domain', title: '来源域名'},
                        {field: 'pv', title: 'PV', width: 100, sort: true},
                        {field: 'uv', title: 'UV', width: 100, sort: true}
                    ]],
                    page: true,
                    limit: 20
                });
            }
        });
    }

    loadData();
    $('#btnQuery').on('click', loadData);
    window.addEventListener('resize', function(){ typeChart.resize(); });
});
</script>
</body>
</html>

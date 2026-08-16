<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>访问概览 - 访问统计</title>
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
            <span>访问概览</span>
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
                <div class="layui-col-md3">
                    <div class="layui-card" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;">
                        <div class="layui-card-body" style="text-align:center;">
                            <div style="font-size:14px;opacity:0.8;">今日PV</div>
                            <div id="todayPv" style="font-size:32px;font-weight:bold;margin:8px 0;">0</div>
                            <div style="font-size:12px;opacity:0.7;">昨日 <span id="yesterdayPv">0</span></div>
                        </div>
                    </div>
                </div>
                <div class="layui-col-md3">
                    <div class="layui-card" style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;">
                        <div class="layui-card-body" style="text-align:center;">
                            <div style="font-size:14px;opacity:0.8;">今日UV</div>
                            <div id="todayUv" style="font-size:32px;font-weight:bold;margin:8px 0;">0</div>
                            <div style="font-size:12px;opacity:0.7;">昨日 <span id="yesterdayUv">0</span></div>
                        </div>
                    </div>
                </div>
                <div class="layui-col-md3">
                    <div class="layui-card" style="background:linear-gradient(135deg,#4facfe,#00f2fe);color:#fff;">
                        <div class="layui-card-body" style="text-align:center;">
                            <div style="font-size:14px;opacity:0.8;">累计PV</div>
                            <div id="totalPv" style="font-size:32px;font-weight:bold;margin:8px 0;">0</div>
                            <div style="font-size:12px;opacity:0.7;">累计UV <span id="totalUv">0</span></div>
                        </div>
                    </div>
                </div>
                <div class="layui-col-md3">
                    <div class="layui-card" style="background:linear-gradient(135deg,#43e97b,#38f9d7);color:#fff;">
                        <div class="layui-card-body" style="text-align:center;">
                            <div style="font-size:14px;opacity:0.8;">平均跳出率</div>
                            <div id="avgBounceRate" style="font-size:32px;font-weight:bold;margin:8px 0;">0%</div>
                            <div style="font-size:12px;opacity:0.7;">独立IP <span id="totalIp">0</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-card" style="margin-top:15px;">
                <div class="layui-card-header">访问趋势</div>
                <div class="layui-card-body">
                    <div id="trendChart" style="height:350px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script src="{{ asset('apps/cmspro.sitestat/js/echarts.min.js') }}"></script>
<script>
layui.use(['laydate', 'jquery'], function(){
    var laydate = layui.laydate;
    var $ = layui.jquery;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    // 日期选择器
    laydate.render({ elem: '#startDate', value: new Date(new Date().getTime() - 30*24*3600*1000) });
    laydate.render({ elem: '#endDate', value: new Date() });

    var trendChart = echarts.init(document.getElementById('trendChart'));

    function loadData() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();

        $.get('/admin/cmspro/sitestat/api/dashboard', {
            start_date: startDate,
            end_date: endDate
        }, function(res) {
            if (res.code === 0) {
                var d = res.data;
                $('#todayPv').text(d.today_pv);
                $('#todayUv').text(d.today_uv);
                $('#yesterdayPv').text(d.yesterday_pv);
                $('#yesterdayUv').text(d.yesterday_uv);
                $('#totalPv').text(d.total_pv);
                $('#totalUv').text(d.total_uv);
                $('#totalIp').text(d.total_ip);
                $('#avgBounceRate').text(d.avg_bounce_rate + '%');

                // 趋势图
                var dates = d.trend.map(function(i){ return i.date; });
                trendChart.setOption({
                    tooltip: { trigger: 'axis' },
                    legend: { data: ['PV', 'UV', 'IP数'] },
                    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'category', data: dates },
                    yAxis: { type: 'value' },
                    series: [
                        { name: 'PV', type: 'line', data: d.trend.map(function(i){ return i.pv; }), smooth: true, itemStyle: { color: '#667eea' } },
                        { name: 'UV', type: 'line', data: d.trend.map(function(i){ return i.uv; }), smooth: true, itemStyle: { color: '#f5576c' } },
                        { name: 'IP数', type: 'line', data: d.trend.map(function(i){ return i.ip_count; }), smooth: true, itemStyle: { color: '#43e97b' } }
                    ]
                });
            }
        });
    }

    loadData();

    $('#btnQuery').on('click', loadData);

    window.addEventListener('resize', function(){ trendChart.resize(); });
});
</script>
</body>
</html>

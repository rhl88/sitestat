<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>蜘蛛统计 - 访问统计</title>
    <link rel="stylesheet" href="{{ asset('CmsProUi/component/pear/css/pear.css') }}">
    <link rel="stylesheet" href="{{ asset('CmsProUi/font-awesome/4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/reset.css') }}">
    <style>
        .block-cell { position: relative; padding-right: 76px; }
        .block-cell .block-btn { position: absolute; right: 0; top: 50%; transform: translateY(-50%); display: none; margin-left: 0; }
        .block-cell:hover .block-btn { display: inline-block; }
        .block-cell:hover .block-value { color: #ff4d4f; }
        .block-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 10px;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 6px;
            background: #fafafa;
        }
        .block-item-value {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 10px;
            font-size: 12px;
            color: #333;
        }
        .block-item-ops { flex-shrink: 0; }
    </style>
</head>
<body>
<div class="pear-container">
    <div class="layui-card">
        <div class="layui-card-header">
            <span>蜘蛛统计</span>
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
            <div class="layui-tab layui-tab-brief" lay-filter="spiderTab">
                <ul class="layui-tab-title">
                    <li class="layui-this">蜘蛛统计</li>
                    <li>访问记录</li>
                </ul>
                <div class="layui-tab-content">

                    {{-- ========== Tab 1：蜘蛛统计（默认打开） ========== --}}
                    <div class="layui-tab-item layui-show">
                        <div class="layui-row layui-col-space15">
                            <div class="layui-col-md6">
                                <div class="layui-card">
                                    <div class="layui-card-header">蜘蛛访问趋势</div>
                                    <div class="layui-card-body">
                                        <div id="trendChart" style="height:300px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md6">
                                <div class="layui-card">
                                    <div class="layui-card-header">蜘蛛类型分布</div>
                                    <div class="layui-card-body">
                                        <div id="pieChart" style="height:300px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="layui-card" style="margin-top:15px;">
                            <div class="layui-card-header">蜘蛛排行</div>
                            <div class="layui-card-body">
                                <table id="spiderRankTable" lay-filter="spiderRankTable"></table>
                            </div>
                        </div>
                    </div>

                    {{-- ========== Tab 2：访问记录 ========== --}}
                    <div class="layui-tab-item">
                        <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:12px;">
                            <div style="width:160px;">
                                <select id="spiderFilter" class="layui-input">
                                    <option value="">全部蜘蛛</option>
                                </select>
                            </div>
                            <button class="layui-btn layui-btn-sm layui-btn-primary" id="btnBlockManage" style="margin-left:8px;"><i class="fa fa-ban"></i> 黑名单管理</button>
                        </div>
                        <table id="spiderVisitTable" lay-filter="spiderVisitTable"></table>
                    </div>
                </div>
            </div>
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

<script type="text/html" id="spiderNameTpl">
@verbatim
{{ d.spider_name }}
{{# if(d.is_blocked){ }}
<span style="color:#999;font-size:12px;">已拉黑</span>
{{# } }}
@endverbatim
</script>

<script type="text/html" id="ipTpl">
@verbatim
<div class="block-cell">
    <span class="block-value">{{ d.ip }}</span>
    <button class="layui-btn layui-btn-xs layui-btn-danger block-btn" data-type="ip" data-value="{{ d.ip }}"><i class="fa fa-ban"></i> 拉黑</button>
</div>
@endverbatim
</script>

<script type="text/html" id="regionTpl">
@verbatim
{{# if(d.region){ }}
<span>{{ d.region }}</span>
{{# } else { }}
<span style="color:#999;">-</span>
{{# } }}
@endverbatim
</script>

<script type="text/html" id="uaTpl">
@verbatim
<div class="block-cell">
    <span class="block-value">{{ d.user_agent }}</span>
    <button class="layui-btn layui-btn-xs layui-btn-danger block-btn" data-type="ua" data-value="{{ d.user_agent }}"><i class="fa fa-ban"></i> 拉黑</button>
</div>
@endverbatim
</script>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script src="{{ asset('apps/cmspro.sitestat/js/echarts.min.js') }}"></script>

<!-- 黑名单管理弹窗 -->
<div id="blockManageDialog" style="display:none;">
    <div style="padding:15px;">
        <div class="layui-form-item">
            <div class="layui-input-group" style="display:flex;">
                <select id="blockTypeFilter" class="layui-input" style="width:100px;flex-shrink:0;">
                    <option value="ip">IP</option>
                    <option value="ua">UA</option>
                </select>
                <input type="text" id="blockKeyword" placeholder="输入 IP 或 UA 关键词查询" class="layui-input" style="flex:1;margin:0 8px;">
                <button class="layui-btn layui-btn-sm" id="btnBlockSearch"><i class="fa fa-search"></i> 查询</button>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-group" style="display:flex;">
                <select id="blockTypeAdd" class="layui-input" style="width:100px;flex-shrink:0;">
                    <option value="ip">IP</option>
                    <option value="ua">UA</option>
                </select>
                <input type="text" id="blockValueAdd" placeholder="输入要拉黑的 IP 或 UA" class="layui-input" style="flex:1;margin:0 8px;">
                <button class="layui-btn layui-btn-sm layui-btn-danger" id="btnBlockAdd"><i class="fa fa-plus"></i> 添加</button>
            </div>
        </div>
        <div id="blockListContainer" style="max-height:380px;overflow-y:auto;margin-top:10px;"></div>
    </div>
</div>
<script>
layui.use(['table', 'laydate', 'jquery', 'layer', 'element'], function(){
    var table = layui.table;
    var laydate = layui.laydate;
    var layer = layui.layer;
    var element = layui.element;
    var $ = layui.jquery;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    laydate.render({ elem: '#startDate', value: new Date(new Date().getTime() - 30*24*3600*1000) });
    laydate.render({ elem: '#endDate', value: new Date() });

    var trendChart = echarts.init(document.getElementById('trendChart'));
    var pieChart = echarts.init(document.getElementById('pieChart'));

    function loadData() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();

        $.get('/admin/cmspro/sitestat/api/spider', {
            start_date: startDate,
            end_date: endDate
        }, function(res) {
            if (res.code === 0) {
                var d = res.data;

                // 趋势图
                var dates = d.daily_trend.map(function(i){ return i.date; });
                var visits = d.daily_trend.map(function(i){ return i.visit_count; });
                var ips = d.daily_trend.map(function(i){ return i.ip_count; });

                trendChart.setOption({
                    tooltip: { trigger: 'axis' },
                    legend: { data: ['访问次数', 'IP数'] },
                    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
                    xAxis: { type: 'category', data: dates, axisLabel: { rotate: 30 } },
                    yAxis: { type: 'value' },
                    series: [
                        { name: '访问次数', type: 'line', data: visits, smooth: true, areaStyle: { opacity: 0.1 } },
                        { name: 'IP数', type: 'line', data: ips, smooth: true, areaStyle: { opacity: 0.1 } }
                    ]
                });

                // 饼图
                var pieData = d.by_spider.map(function(i){
                    return { name: i.spider_name, value: i.visit_count };
                });
                pieChart.setOption({
                    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                    series: [{
                        type: 'pie',
                        radius: ['40%', '70%'],
                        data: pieData,
                        emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.5)' } }
                    }]
                });

                // 蜘蛛排行表
                table.render({
                    elem: '#spiderRankTable',
                    data: d.by_spider,
                    cols: [[
                        {type: 'numbers', title: '#'},
                        {field: 'spider_name', title: '蜘蛛名称'},
                        {field: 'visit_count', title: '访问次数', sort: true},
                        {field: 'ip_count', title: 'IP数', sort: true}
                    ]],
                    page: true,
                    limit: 10
                });

                // 填充蜘蛛筛选下拉框
                var $spiderFilter = $('#spiderFilter');
                var currentVal = $spiderFilter.val();
                $spiderFilter.empty().append('<option value="">全部蜘蛛</option>');
                d.by_spider.forEach(function(item){
                    $spiderFilter.append('<option value="' + item.spider_name + '">' + item.spider_name + '</option>');
                });
                if (currentVal) {
                    $spiderFilter.val(currentVal);
                }
            }
        });
    }

    // 下拉框变化时重新加载访问记录（原生 select change 事件）
    $('#spiderFilter').on('change', function(){
        table.reload('spiderVisitTable', {
            where: {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                spider_name: $(this).val()
            },
            page: { curr: 1 }
        });
    });

    loadData();

    // 访问记录表格只渲染一次（后端分页）
    table.render({
        elem: '#spiderVisitTable',
        url: '/admin/cmspro/sitestat/api/spider/visits',
        where: {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            spider_name: ''
        },
        page: true,
        limits: [10, 20, 50, 100],
        limit: 20,
        cols: [[
            {field: 'spider_name', title: '蜘蛛', templet: '#spiderNameTpl'},
            {field: 'ip', title: 'IP', templet: '#ipTpl'},
            {field: 'region', title: '地区', width: 160, templet: '#regionTpl'},
            {field: 'path', title: '页面', templet: '#urlTpl'},
            {field: 'user_agent', title: 'UA', templet: '#uaTpl'},
            {field: 'create_time', title: '访问时间'}
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

    // 表格在隐藏的 Tab 内渲染时宽度为 0，切换到「访问记录」时重新计算列宽
    element.on('tab(spiderTab)', function(data) {
        if (data.index === 1) {
            setTimeout(function() {
                table.resize('spiderVisitTable');
            }, 50);
        }
    });

    $('#btnQuery').on('click', function() {
        loadData();
        table.reload('spiderVisitTable', {
            where: {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                spider_name: $('#spiderFilter').val()
            },
            page: { curr: 1 }
        });
    });

    // 拉黑按钮点击（事件委托，兼容表格分页重渲染）
    $(document).on('click', '.block-btn', function() {
        var type = $(this).data('type');
        var value = $(this).data('value');
        var label = type === 'ip' ? 'IP' : 'UA';

        layer.confirm('确定将' + label + '「' + value + '」加入黑名单吗？加入后该' + label + '的蜘蛛访问将被拦截（403）。', {
            icon: 3,
            title: '拉黑确认'
        }, function(index) {
            $.post('/admin/cmspro/sitestat/api/spider/block', {
                type: type,
                value: value
            }, function(res) {
                if (res.code === 0) {
                    layer.msg(res.message, {icon: 1});
                } else {
                    layer.msg(res.message || '操作失败', {icon: 2});
                }
            });
            layer.close(index);
        });
    });

    // ===== 黑名单管理 =====
    var blockDialogIndex = null;
    var blockList = { ips: [], uas: [] };

    // 渲染黑名单列表
    function renderBlockList() {
        var type = $('#blockTypeFilter').val();
        var keyword = ($('#blockKeyword').val() || '').trim().toLowerCase();
        var items = type === 'ip' ? blockList.ips : blockList.uas;

        if (keyword) {
            items = items.filter(function(item) {
                return item.toLowerCase().indexOf(keyword) !== -1;
            });
        }

        var $container = $('#blockListContainer');
        if (items.length === 0) {
            $container.html('<div style="color:#999;text-align:center;padding:30px 0;">暂无' + (type === 'ip' ? 'IP' : 'UA') + '黑名单' + (keyword ? '（无匹配结果）' : '') + '</div>');
            return;
        }

        var html = '';
        items.forEach(function(item) {
            html += '<div class="block-item">'
                + '<span class="block-item-value" title="' + item + '">' + item + '</span>'
                + '<span class="block-item-ops">'
                + '<button class="layui-btn layui-btn-xs block-edit" data-type="' + type + '" data-value="' + item + '"><i class="fa fa-pencil"></i> 编辑</button>'
                + '<button class="layui-btn layui-btn-xs layui-btn-danger block-del" data-type="' + type + '" data-value="' + item + '"><i class="fa fa-trash"></i> 删除</button>'
                + '</span>'
                + '</div>';
        });
        $container.html(html);
    }

    // 加载黑名单
    function loadBlockList() {
        $.get('/admin/cmspro/sitestat/api/spider/blocked-list', function(res) {
            if (res.code === 0) {
                blockList = res.data;
                renderBlockList();
            }
        });
    }

    // 打开黑名单管理弹窗
    $('#btnBlockManage').on('click', function() {
        blockDialogIndex = layer.open({
            type: 1,
            title: '黑名单管理',
            area: ['560px', '560px'],
            shade: [0.5, '#000'],
            shadeClose: 0,
            closeBtn: 1,
            content: $('#blockManageDialog'),
            success: function() {
                loadBlockList();
            }
        });
    });

    // 查询
    $('#btnBlockSearch').on('click', function() {
        renderBlockList();
    });
    $('#blockKeyword').on('keydown', function(e) {
        if (e.keyCode === 13) {
            renderBlockList();
        }
    });

    // 添加
    $('#btnBlockAdd').on('click', function() {
        var type = $('#blockTypeAdd').val();
        var value = ($('#blockValueAdd').val() || '').trim();
        if (!value) {
            layer.msg('请输入要拉黑的' + (type === 'ip' ? 'IP' : 'UA'), {icon: 2});
            return;
        }
        $.post('/admin/cmspro/sitestat/api/spider/block', {
            type: type,
            value: value
        }, function(res) {
            if (res.code === 0) {
                layer.msg(res.message, {icon: 1});
                $('#blockValueAdd').val('');
                loadBlockList();
            } else {
                layer.msg(res.message || '操作失败', {icon: 2});
            }
        });
    });

    // 编辑（事件委托）
    $(document).on('click', '.block-edit', function() {
        var type = $(this).data('type');
        var value = $(this).data('value');
        var label = type === 'ip' ? 'IP' : 'UA';

        layer.prompt({
            title: '编辑' + label + '黑名单',
            value: value,
            formType: 0
        }, function(newValue, index) {
            newValue = (newValue || '').trim();
            if (!newValue) {
                layer.msg('值不能为空', {icon: 2});
                return;
            }
            if (newValue === value) {
                layer.close(index);
                return;
            }
            // 先删除旧值，再添加新值
            $.post('/admin/cmspro/sitestat/api/spider/unblock', {
                type: type,
                value: value
            }, function() {
                $.post('/admin/cmspro/sitestat/api/spider/block', {
                    type: type,
                    value: newValue
                }, function(res) {
                    if (res.code === 0) {
                        layer.msg('已更新', {icon: 1});
                        loadBlockList();
                    } else {
                        layer.msg(res.message || '操作失败', {icon: 2});
                    }
                });
            });
            layer.close(index);
        });
    });

    // 删除（事件委托）
    $(document).on('click', '.block-del', function() {
        var type = $(this).data('type');
        var value = $(this).data('value');
        var label = type === 'ip' ? 'IP' : 'UA';

        layer.confirm('确定删除' + label + '「' + value + '」的黑名单吗？', {
            icon: 3,
            title: '删除确认'
        }, function(index) {
            $.post('/admin/cmspro/sitestat/api/spider/unblock', {
                type: type,
                value: value
            }, function(res) {
                if (res.code === 0) {
                    layer.msg(res.message, {icon: 1});
                    loadBlockList();
                } else {
                    layer.msg(res.message || '操作失败', {icon: 2});
                }
            });
            layer.close(index);
        });
    });

    window.addEventListener('resize', function(){
        trendChart.resize();
        pieChart.resize();
    });
});
</script>
</body>
</html>

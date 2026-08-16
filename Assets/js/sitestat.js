/**
 * CmsPro 访问统计 SDK
 * 使用方式：<script src="/apps/cmspro.sitestat/js/sitestat.js"></script>
 */
(function() {
    'use strict';

    var API_URL = '/api/sitestat/collect';

    // 生成或读取访客标识
    function getOrCreateSid() {
        var sidKey = 'cmspro_sitestat_sid';
        var sid = getCookie(sidKey);
        if (!sid) {
            sid = generateId();
            setCookie(sidKey, sid, 365);
        }
        return sid;
    }

    // 生成唯一ID
    function generateId() {
        return 'ss_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Cookie操作
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
    }

    // 获取屏幕分辨率
    function getScreenResolution() {
        return screen.width + 'x' + screen.height;
    }

    // 上报数据
    function track() {
        var data = {
            sid: getOrCreateSid(),
            url: location.href,
            path: location.pathname,
            title: document.title,
            referer: document.referrer,
            ua: navigator.userAgent,
            screen: getScreenResolution()
        };

        // 使用navigator.sendBeacon优先（页面卸载时更可靠）
        if (navigator.sendBeacon) {
            var formData = new FormData();
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    formData.append(key, data[key]);
                }
            }
            navigator.sendBeacon(API_URL, formData);
        } else {
            // 降级为同步XHR
            try {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', API_URL, false);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send(encodeData(data));
            } catch (e) {
                // 静默失败
            }
        }
    }

    // 编码数据
    function encodeData(data) {
        var pairs = [];
        for (var key in data) {
            if (data.hasOwnProperty(key) && data[key] !== undefined && data[key] !== null) {
                pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
        }
        return pairs.join('&');
    }

    // 页面加载完成后上报
    if (document.readyState === 'complete') {
        track();
    } else {
        window.addEventListener('load', track);
    }

    // SPA 路由变化监听
    var lastPath = location.pathname;
    function checkRouteChange() {
        if (location.pathname !== lastPath) {
            lastPath = location.pathname;
            track();
        }
    }

    // 监听 popstate 事件（浏览器前进/后退）
    window.addEventListener('popstate', checkRouteChange);

    // 拦截 pushState 和 replaceState
    var origPushState = history.pushState;
    var origReplaceState = history.replaceState;

    history.pushState = function() {
        origPushState.apply(this, arguments);
        setTimeout(checkRouteChange, 0);
    };

    history.replaceState = function() {
        origReplaceState.apply(this, arguments);
        setTimeout(checkRouteChange, 0);
    };
})();

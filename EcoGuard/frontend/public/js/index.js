$(function () {
    const trashTypeChart = echarts.init(document.getElementById('trashTypeChart'));
    let refreshTimer = null;

    function safeResize(chart) {
        if (!chart) return;
        try {
            chart.resize();
        } catch (error) {
        }
    }

    function renderRobotList(robotList) {
        const $list = $('#robotListContainer');
        $list.empty();
        (robotList || []).forEach(robot => {
            const statusClass = robot.status === 'ONLINE' ? 'status-online-text' : 'status-offline-text';
            const statusText = robot.status === 'ONLINE' ? '● 在线' : '● 离线';
            $list.append(`
                <li class="list-item">
                    <span>${robot.device_id}</span>
                    <span>${robot.name}</span>
                    <span class="${statusClass}">${statusText}</span>
                </li>
            `);
        });
    }

    function applyPieChart(pieData) {
        trashTypeChart.setOption({
            tooltip: {trigger: 'item'},
            series: [{
                type: 'pie',
                radius: ['40%', '70%'],
                data: pieData,
                label: {color: '#82b2c6'},
                itemStyle: {borderRadius: 5}
            }]
        });
    }

    function loadDashboard() {
        $.get('/api/stats/summary', function (payload) {
            if (!payload.ok) return;

            applyPieChart(payload.pie_data);
            renderRobotList(payload.robot_list);

            if (window.syncRobotMarkers && payload.robot_list) {
                window.syncRobotMarkers(payload.robot_list);
            }
        });
    }

    window.onresize = function () {
        safeResize(trashTypeChart);
        if (typeof workTrendChart !== 'undefined' && workTrendChart) {
            safeResize(workTrendChart);
        }
        if (typeof robotBatteryChart !== 'undefined' && robotBatteryChart) {
            safeResize(robotBatteryChart);
        }
    };

    function startRefreshLoop() {
        if (refreshTimer) return;
        refreshTimer = setInterval(loadDashboard, 3000);
    }

    function stopRefreshLoop() {
        if (!refreshTimer) return;
        clearInterval(refreshTimer);
        refreshTimer = null;
    }

    loadDashboard();
    startRefreshLoop();

    function syncPageVisibility() {
        if (document.hidden) {
            stopRefreshLoop();
            stopClockLoop();
            return;
        }
        loadDashboard();
        startRefreshLoop();
        renderClock();
        startClockLoop();
    }

    document.addEventListener('visibilitychange', syncPageVisibility);
    window.addEventListener('beforeunload', function () {
        stopRefreshLoop();
        stopClockLoop();
    });
});

const WEEKS = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];


function formatDateTime(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    const second = String(date.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
}

function renderClock() {
    const now = new Date();
    const timeStr = formatDateTime(now);
    $('.header-date').text(timeStr);
    $('.header-week').text(WEEKS[now.getDay()]);
}

let clockTimer = null;

function startClockLoop() {
    if (clockTimer) return;
    clockTimer = setInterval(renderClock, 1000);
}

function stopClockLoop() {
    if (!clockTimer) return;
    clearInterval(clockTimer);
    clockTimer = null;
}

startClockLoop();
renderClock();

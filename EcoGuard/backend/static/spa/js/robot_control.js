document.addEventListener('DOMContentLoaded', () => {
    const robot = window.ROBOT || {};
    const map = L.map('robotControlMap').setView([30, 110], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const dotIcon = L.divIcon({
        className: 'map-dot-icon',
        html: '<span class="map-dot"></span>',
        iconSize: [14, 14],
        iconAnchor: [7, 7]
    });
    let robotMarker = null;
    let targetMarker = null;
    let robotPollTimer = null;

    const statusBadge = document.getElementById('robotStatusBadge');
    const batteryText = document.getElementById('robotBatteryText');
    const batteryBar = document.getElementById('robotBatteryBar');
    const lastHeartbeatEl = document.getElementById('robotLastHeartbeat');
    const positionEl = document.getElementById('robotPosition');
    const targetEl = document.getElementById('robotTarget');
    const logList = document.getElementById('robotLogList');
    const customCmdInput = document.getElementById('customCmd');
    const sendCustomCmdBtn = document.getElementById('btnSendCustomCmd');

    async function getJson(url) {
        const httpResponse = await fetch(url);
        return httpResponse.json();
    }

    async function postJson(url, body) {
        const httpResponse = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body)
        });
        return httpResponse.json();
    }

    function addLog(msg) {
        if (!logList) return;
        const now = new Date();
        const timeStr = now.toTimeString().slice(0, 8);
        const item = document.createElement('div');
        item.className = 'robot-log-item';
        item.innerHTML = `<span class="time">${timeStr}</span><span class="text">${msg}</span>`;
        logList.appendChild(item);
        while (logList.children.length > 50) {
            logList.removeChild(logList.firstChild);
        }
        logList.scrollTop = logList.scrollHeight;
    }

    function updateStatusUI(currentRobot) {
        document.title = `机器人控制 - ${currentRobot.name} (${currentRobot.status})`;

        if (statusBadge) {
            statusBadge.textContent = currentRobot.status || 'UNKNOWN';
            statusBadge.classList.remove('status-online', 'status-offline');
            statusBadge.classList.add(currentRobot.status === 'ONLINE' ? 'status-online' : 'status-offline');
        }

        if (currentRobot.battery != null) {
            const batteryValue = Math.max(0, Math.min(100, Number(currentRobot.battery)));
            if (batteryText) batteryText.textContent = `${batteryValue.toFixed(0)}%`;
            if (batteryBar) batteryBar.style.width = `${batteryValue}%`;
        }

        if (positionEl && currentRobot.lat != null && currentRobot.lng != null) {
            positionEl.textContent = `${currentRobot.lat.toFixed(5)}, ${currentRobot.lng.toFixed(5)}`;
        }
        if (lastHeartbeatEl && currentRobot.last_heartbeat) {
            lastHeartbeatEl.textContent = currentRobot.last_heartbeat;
        }
    }

    function updateRobotMarker(currentRobot) {
        if (currentRobot.lat == null || currentRobot.lng == null) return;

        if (robotMarker) {
            robotMarker.setLatLng([currentRobot.lat, currentRobot.lng]);
            return;
        }

        robotMarker = L.marker([currentRobot.lat, currentRobot.lng], {icon: dotIcon})
            .addTo(map)
            .bindPopup(`<b>${currentRobot.name}</b><br>${currentRobot.device_id}`);
        map.setView([currentRobot.lat, currentRobot.lng], 16);
    }

    function updateTargetUI(currentRobot) {
        const target = currentRobot.target;
        if (!target || target.lat == null || target.lng == null) return;

        if (targetEl) targetEl.textContent = `${target.lat.toFixed(5)}, ${target.lng.toFixed(5)}`;

        if (targetMarker) {
            targetMarker.setLatLng([target.lat, target.lng]);
        } else {
            targetMarker = L.marker([target.lat, target.lng], {icon: dotIcon}).addTo(map).bindPopup('目标位置');
        }

        const navLat = document.getElementById('navLat');
        const navLng = document.getElementById('navLng');
        if (navLat && !navLat.value) navLat.value = target.lat;
        if (navLng && !navLng.value) navLng.value = target.lng;
    }

    async function loadRobot() {
        try {
            const payload = await getJson('/api/robot/list');
            if (!payload.ok) return;

            const robotList = payload.robots || [];
            const currentRobot = robotList.find((item) => item.id === robot.id || item.device_id === robot.device_id);
            if (!currentRobot) return;

            updateRobotMarker(currentRobot);
            updateStatusUI(currentRobot);
            updateTargetUI(currentRobot);
        } catch (error) {
            console.warn('fetch robot failed', error);
        }
    }

    function startPolling() {
        if (robotPollTimer) return;
        robotPollTimer = setInterval(loadRobot, 2000);
    }

    function stopPolling() {
        if (!robotPollTimer) return;
        clearInterval(robotPollTimer);
        robotPollTimer = null;
    }

    loadRobot();
    startPolling();

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            return;
        }
        loadRobot();
        startPolling();
    });

    window.addEventListener('beforeunload', stopPolling);

    document.getElementById('btnSetStream').addEventListener('click', () => {
        const url = document.getElementById('streamUrl').value.trim();
        if (!url) return alert('请输入流地址');
        document.getElementById('robotCamera').src = url;
    });

    function sendControlCommand(command, sourceText) {
        const normalizedCommand = String(command || '').trim();
        if (!normalizedCommand) {
            alert('请输入有效指令');
            return;
        }

        addLog(`${sourceText}: ${normalizedCommand}`);
        postJson('/api/robot/control', {id: robot.id, command: normalizedCommand})
            .then((payload) => {
                if (!payload.ok) {
                    addLog(payload.msg || '命令发送失败');
                    alert(payload.msg || '命令发送失败');
                    return;
                }
                addLog(`已下发: ${payload.command || normalizedCommand}`);
            })
            .catch(() => {
                addLog('请求失败');
                alert('请求失败');
            });
    }

    document.querySelectorAll('.ctl-btn').forEach(button => {
        button.addEventListener('click', () => {
            sendControlCommand(button.dataset.cmd, '发送运动命令');
        });
    });

    if (sendCustomCmdBtn && customCmdInput) {
        sendCustomCmdBtn.addEventListener('click', () => {
            const command = customCmdInput.value;
            sendControlCommand(command, '发送自定义指令');
        });

        customCmdInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendControlCommand(customCmdInput.value, '发送自定义指令');
            }
        });
    }

    function sendNavigateCommand(lat, lng, sourceText) {
        addLog(`${sourceText}: ${lat.toFixed(5)}, ${lng.toFixed(5)}`);
        postJson('/api/robot/navigate', {id: robot.id, lat: lat, lng: lng})
            .then((payload) => {
                addLog(payload.msg || (payload.ok ? '导航命令已发送' : '导航失败'));
                alert(payload.msg || (payload.ok ? '已发送' : '失败'));
            }).catch(() => {
                addLog('请求失败');
                alert('请求失败');
            });
    }

    document.getElementById('btnSetNav').addEventListener('click', () => {
        const latitude = parseFloat(document.getElementById('navLat').value);
        const longitude = parseFloat(document.getElementById('navLng').value);
        if (isNaN(latitude) || isNaN(longitude)) return alert('请输入合法坐标');
        sendNavigateCommand(latitude, longitude, '设置导航目标');
    });

    map.on('dblclick', function (ev) {
        const lat = ev.latlng.lat;
        const lng = ev.latlng.lng;
        if (!confirm(`确认让设备 ${robot.device_id} 导航到 (${lat.toFixed(5)}, ${lng.toFixed(5)}) ?`)) return;
        sendNavigateCommand(lat, lng, '地图双击设置导航');
    });
});

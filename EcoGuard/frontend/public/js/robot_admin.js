document.addEventListener('DOMContentLoaded', () => {
    const addBtn = document.getElementById('addRobotBtn');
    const deviceInput = document.getElementById('newDeviceId');
    const nameInput = document.getElementById('newName');
    const table = document.getElementById('robotTableBody');

    const markers = {};
    let selectedRobotId = null;
    let robotPollTimer = null;
    let map = null;

    const dotIcon = L.divIcon({
        className: 'map-dot-icon',
        html: '<span class="map-dot"></span>',
        iconSize: [10, 10],
        iconAnchor: [5, 5]
    });

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

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

    function setSelectedRobot(robotId) {
        selectedRobotId = String(robotId);
        table.querySelectorAll('tr').forEach((rowItem) => rowItem.classList.remove('selected'));
        const selectedRow = table.querySelector(`tr[data-id='${selectedRobotId}']`);
        if (selectedRow) selectedRow.classList.add('selected');
    }

    function updateRow(robot) {
        const row = table.querySelector(`tr[data-id='${robot.id}']`);
        if (!row) return;

        const statusCell = row.querySelector('.col-status');
        const batteryCell = row.querySelector('.col-battery');
        const ipCell = row.querySelector('.col-ip');
        const heartbeatCell = row.querySelector('.col-last');

        if (statusCell) statusCell.innerText = robot.status || '-';
        if (batteryCell) batteryCell.innerText = robot.battery != null ? robot.battery : '-';
        if (ipCell) ipCell.innerText = robot.ip_address || '-';
        if (heartbeatCell) {
            heartbeatCell.innerText = robot.last_heartbeat ? new Date(robot.last_heartbeat).toLocaleString() : '-';
        }
    }

    function updateMapMarkers(robotList) {
        if (!map) return;

        const activeMarkerIds = new Set();
        robotList.forEach((robot) => {
            if (robot.lat == null || robot.lng == null) return;
            const markerId = String(robot.id);
            activeMarkerIds.add(markerId);

            if (markers[markerId]) {
                markers[markerId].setLatLng([robot.lat, robot.lng]);
                return;
            }

            const marker = L.marker([robot.lat, robot.lng], {icon: dotIcon})
                .addTo(map)
                .bindPopup(`<b>${escapeHtml(robot.name)}</b><br>${escapeHtml(robot.device_id)}`);
            marker.on('click', () => setSelectedRobot(robot.id));
            markers[markerId] = marker;
        });

        Object.keys(markers).forEach((markerId) => {
            if (activeMarkerIds.has(markerId)) return;
            try {
                map.removeLayer(markers[markerId]);
            } catch (error) {
            }
            delete markers[markerId];
        });
    }

    async function refreshRobots() {
        try {
            const payload = await getJson('/api/robot/list');
            if (!payload.ok) return;

            const robotList = payload.robots || [];
            robotList.forEach(updateRow);
            updateMapMarkers(robotList);
        } catch (error) {
            console.warn('fetch robots failed', error);
        }
    }

    function startPolling() {
        if (robotPollTimer) return;
        robotPollTimer = setInterval(refreshRobots, 5000);
    }

    function stopPolling() {
        if (!robotPollTimer) return;
        clearInterval(robotPollTimer);
        robotPollTimer = null;
    }

    function bindMapNavigate() {
        if (!map) return;
        map.on('dblclick', async (event) => {
            if (!selectedRobotId) {
                alert('请先在表格中选择一个机器人（单击行或标记）');
                return;
            }

            const latitude = event.latlng.lat;
            const longitude = event.latlng.lng;
            const confirmed = confirm(`确认让设备 ${selectedRobotId} 导航到 (${latitude.toFixed(5)}, ${longitude.toFixed(5)}) ?`);
            if (!confirmed) return;

            try {
                const payload = await postJson('/api/robot/navigate', {
                    id: parseInt(selectedRobotId, 10),
                    lat: latitude,
                    lng: longitude
                });
                alert(payload.msg || (payload.ok ? '命令已发送' : '失败'));
            } catch (error) {
                alert('请求失败');
            }
        });
    }

    function bindAddRobot() {
        addBtn.addEventListener('click', async () => {
            const deviceId = deviceInput.value.trim();
            const name = nameInput.value.trim();
            if (!deviceId || !name) {
                alert('请填写设备ID和名称');
                return;
            }

            try {
                const payload = await postJson('/api/robot/register', {device_id: deviceId, name});
                if (!payload.ok) {
                    alert(payload.msg || '添加失败');
                    return;
                }
                location.reload();
            } catch (error) {
                alert('请求失败');
            }
        });
    }

    function bindDeleteRobot() {
        table.querySelectorAll('.btn-delete').forEach((button) => {
            button.addEventListener('click', async function () {
                const row = this.closest('tr');
                const robotId = row && row.dataset.id;
                if (!robotId) return;
                if (!confirm('确认删除该机器人？')) return;

                try {
                    const payload = await postJson(`/api/robot/delete/${robotId}`, {});
                    if (!payload.ok) {
                        alert(payload.msg || '删除失败');
                        return;
                    }
                    location.reload();
                } catch (error) {
                    alert('请求失败');
                }
            });
        });
    }

    function bindRowSelect() {
        table.querySelectorAll('tr[data-id]').forEach((rowItem) => {
            rowItem.addEventListener('click', () => setSelectedRobot(rowItem.dataset.id));
        });
    }

    try {
        map = L.map('robotMap').setView([30, 110], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
    } catch (error) {
        console.warn('Leaflet init failed', error);
    }

    refreshRobots();
    startPolling();
    bindMapNavigate();
    bindAddRobot();
    bindDeleteRobot();
    bindRowSelect();

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            return;
        }
        refreshRobots();
        startPolling();
    });

    window.addEventListener('beforeunload', stopPolling);
});

$(document).ready(function () {
    const map = L.map('map').setView([30.0, 110.0], 5);
    window._indexMap = map;


    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const dotIcon = L.divIcon({
        className: 'map-dot-icon',
        html: '<span class="map-dot"></span>',
        iconSize: [14, 14],
        iconAnchor: [7, 7]
    });

    window._robotMarkers = window._robotMarkers || {};
    window._taskMarkers = window._taskMarkers || {};

    function buildRobotPopup(robot) {
        const batteryText = robot.battery != null ? `${robot.battery}%` : '';
        return `<b>${robot.name}</b><br>${robot.device_id}<br>${robot.status} ${batteryText}`;
    }

    function renderTaskPopup(location) {
        return `
            <div class="map-popup-card">
                <h4 class="map-popup-title">任务 #${location.id}</h4>
                <p class="map-popup-body">
                    <b>识别结果:</b> <span class="map-popup-highlight">${location.trash_types || '未检测到'}</span>
                </p>
                <p class="map-popup-foot">坐标: ${location.lat.toFixed(4)}, ${location.lng.toFixed(4)}</p>
            </div>
        `;
    }

    function syncTaskMarkers(locations) {
        const existingTaskIds = new Set(Object.keys(window._taskMarkers));
        (locations || []).forEach(location => {
            if (location.lat == null || location.lng == null || location.id == null) return;
            const taskId = String(location.id);
            existingTaskIds.delete(taskId);
            const popupHtml = renderTaskPopup(location);

            if (window._taskMarkers[taskId]) {
                window._taskMarkers[taskId].setLatLng([location.lat, location.lng]);
                window._taskMarkers[taskId].setPopupContent(popupHtml);
                return;
            }

            const marker = L.marker([location.lat, location.lng], {icon: dotIcon}).addTo(map);
            marker.bindPopup(popupHtml, {
                closeButton: false,
                offset: L.point(0, -15)
            });
            marker.on('mouseover', function () {
                this.openPopup();
            });
            marker.on('mouseout', function () {
                this.closePopup();
            });
            window._taskMarkers[taskId] = marker;
        });

        existingTaskIds.forEach(id => {
            try {
                map.removeLayer(window._taskMarkers[id]);
            } catch (e) {
            }
            delete window._taskMarkers[id];
        });
    }

    function loadMapData() {
        fetch('/api/stats/summary')
            .then((response) => response.json())
            .then(payload => {
                if (!payload.ok) return;

                syncTaskMarkers(payload.locations || []);
                syncRobotMarkers(payload.robot_list || []);
            })
            .catch(error => console.error("地图数据加载失败:", error));
    }

    function syncRobotMarkers(robots) {
        const existingIds = new Set(Object.keys(window._robotMarkers));
        robots.forEach(robot => {
            if (robot.lat == null || robot.lng == null) return;
            const id = String(robot.device_id || robot.id);
            const popupHtml = buildRobotPopup(robot);
            existingIds.delete(id);
            if (window._robotMarkers[id]) {
                window._robotMarkers[id].setLatLng([robot.lat, robot.lng]);
                if (window._robotMarkers[id].getPopup()) {
                    window._robotMarkers[id].setPopupContent(popupHtml);
                }
            } else {
                window._robotMarkers[id] = L.marker([robot.lat, robot.lng], {icon: dotIcon})
                    .addTo(map)
                    .bindPopup(popupHtml);
            }
        });

        existingIds.forEach(id => {
            try {
                map.removeLayer(window._robotMarkers[id]);
            } catch (e) {
            }
            delete window._robotMarkers[id];
        });
    }

    window.syncRobotMarkers = syncRobotMarkers;


    loadMapData();
    setTimeout(function () {
        map.invalidateSize(true);
    }, 500);

    $(window).on('resize', function () {
        map.invalidateSize();
    });
});

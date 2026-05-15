<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import L from 'leaflet'
import { getJson, postJson } from '../lib/api'
import { escapeHtml } from '../lib/escape'
import { pushFlash } from '../stores/session'

const robots = ref([])
const deviceId = ref('')
const name = ref('')
const selectedRobotId = ref(null)
let map = null
let pollTimer = null
const markers = {}

function resetRobotForm() {
  deviceId.value = ''
  name.value = ''
}

async function runRobotAction(action, successMessage, errorMessage, options = {}) {
  try {
    await action()
    pushFlash(successMessage, 'success')
    if (options.reload !== false) {
      await loadRobots()
    }
  } catch (error) {
    pushFlash(error.message || errorMessage, 'error')
  }
}

function buildRobotPopup(robot) {
  return `<b>${escapeHtml(robot.name || '-')}</b><br>${escapeHtml(robot.device_id || '-')}`
}

function setSelectedRobot(robotId) {
  selectedRobotId.value = String(robotId)
}

function renderMarkers(robotList) {
  const activeIds = new Set()
  robotList.forEach((robot) => {
    if (robot.lat == null || robot.lng == null || !map) {
      return
    }
    const markerId = String(robot.id)
    activeIds.add(markerId)
    if (markers[markerId]) {
      markers[markerId].setLatLng([robot.lat, robot.lng])
      markers[markerId].setPopupContent(buildRobotPopup(robot))
      return
    }
    markers[markerId] = L.marker([robot.lat, robot.lng], {
      icon: L.divIcon({ className: 'map-dot-icon', html: '<span class="map-dot map-dot-robot"></span>', iconSize: [10, 10], iconAnchor: [5, 5] }),
    }).addTo(map).bindPopup(buildRobotPopup(robot))
    markers[markerId].on('click', () => setSelectedRobot(robot.id))
  })

  Object.keys(markers).forEach((markerId) => {
    if (activeIds.has(markerId)) return
    map.removeLayer(markers[markerId])
    delete markers[markerId]
  })
}

async function loadRobots() {
  try {
    const payload = await getJson('/api/robot/list')
    robots.value = payload.robots || []
    renderMarkers(robots.value)
  } catch (error) {
    pushFlash(error.message || '机器人列表加载失败', 'error')
  }
}

async function addRobot() {
  await runRobotAction(
    async () => {
      await postJson('/api/robot/register', { device_id: deviceId.value.trim(), name: name.value.trim() })
      resetRobotForm()
    },
    '机器人添加成功',
    '添加失败',
  )
}

async function deleteRobot(robotId) {
  if (!window.confirm('确认删除该机器人？')) {
    return
  }

  await runRobotAction(
    () => postJson(`/api/robot/delete/${robotId}`, {}),
    '机器人已删除',
    '删除失败',
  )
}

async function navigateRobot(event) {
  if (!selectedRobotId.value) {
    pushFlash('请先在表格中选择一个机器人', 'warning')
    return
  }

  const latitude = event.latlng.lat
  const longitude = event.latlng.lng
  if (!window.confirm(`确认让设备 ${selectedRobotId.value} 导航到 (${latitude.toFixed(5)}, ${longitude.toFixed(5)}) ?`)) {
    return
  }

  try {
    const payload = await postJson('/api/robot/navigate', {
      id: Number(selectedRobotId.value),
      lat: latitude,
      lng: longitude,
    })
    pushFlash(payload.msg || '导航命令已发送', 'success')
  } catch (error) {
    pushFlash(error.message || '导航失败', 'error')
  }
}

function onResize() {
  map?.invalidateSize()
}

onMounted(() => {
  map = L.map('robotMap').setView([30, 110], 5)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map)
  map.on('dblclick', navigateRobot)
  loadRobots()
  pollTimer = window.setInterval(loadRobots, 5000)
  window.setTimeout(() => map?.invalidateSize(), 500)
  window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
  if (pollTimer) window.clearInterval(pollTimer)
  window.removeEventListener('resize', onResize)
  map?.remove()
})
</script>

<template>
  <div class="layout-content robot-admin-layout">
    <div class="layout-left robot-admin-left">
      <div class="layout-inner-left layout-full-width">
        <div class="panel control-pages robot-admin-card">
          <div class="panel-title">机器人管理</div>
          <div class="chart-wrap panel-body">
            <div class="form-row form-row-wide mb-12">
              <input v-model.trim="deviceId" class="input-control" placeholder="设备ID">
              <input v-model.trim="name" class="input-control" placeholder="名称">
              <button type="button" class="btn-add-robot" @click="addRobot">添加</button>
            </div>
            <div class="table-wrap max-h-520">
              <table class="data-table">
                <thead>
                  <tr><th>名称</th><th>状态</th><th>操作</th></tr>
                </thead>
                <tbody id="robotTableBody">
                  <tr
                    v-for="robot in robots"
                    :key="robot.id"
                    :class="{ selected: String(robot.id) === selectedRobotId }"
                    @click="setSelectedRobot(robot.id)"
                  >
                    <td>{{ robot.name }}</td>
                    <td class="col-status">{{ robot.status }}</td>
                    <td class="col-actions">
                      <RouterLink class="btn-operate" :to="`/robot/${robot.id}`">操作</RouterLink>
                      <button type="button" class="btn-delete" @click.stop="deleteRobot(robot.id)">删除</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="layout-center robot-admin-right">
      <div class="panel map-main robot-admin-card">
        <div class="panel-title">机器人实时地图</div>
        <div class="chart-wrap map-wrap">
          <div class="map-box">
            <div id="robotMap" class="robot-map"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.robot-admin-layout {
  display: flex;
  gap: 16px;
  min-height: calc(100vh - 60px);
  padding: 12px;
  background: #f9fafb;
}

.robot-admin-left {
  flex: 0 0 360px;
  width: 360px;
  max-height: calc(100vh - 60px);
  overflow: auto;
  padding-right: 2px;
}

.robot-admin-right {
  flex: 1 1 auto;
  min-width: 0;
}

.robot-admin-card {
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.robot-admin-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.robot-admin-layout .panel-title {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  border-radius: 12px 12px 0 0;
}

.robot-admin-layout .chart-wrap {
  background: #fff;
}

.btn-add-robot {
  background: #10b981;
  color: #fff;
  border: 1px solid #10b981;
  border-radius: 6px;
  padding: 8px 16px;
  font-weight: 700;
}

.btn-add-robot:hover {
  background: #0e9f6e;
  border-color: #0e9f6e;
}

.robot-admin-layout .table-wrap {
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.robot-admin-layout .data-table {
  border-collapse: separate;
  border-spacing: 0 8px;
}

.robot-admin-layout .data-table thead th {
  background: #f3f4f6;
  color: #374151;
}

.robot-admin-layout .data-table tbody tr {
  background: #fff;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.robot-admin-layout .data-table tbody tr:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.1);
}

.robot-admin-layout .data-table tbody tr td:first-child {
  border-radius: 10px 0 0 10px;
}

.robot-admin-layout .data-table tbody tr td:last-child {
  border-radius: 0 10px 10px 0;
}

.robot-admin-layout .map-main,
.robot-admin-layout .map-wrap,
.robot-admin-layout .map-box,
.robot-admin-layout #robotMap,
.robot-admin-layout .robot-map {
  width: 100%;
  min-height: 500px;
}

.robot-admin-layout #robotMap,
.robot-admin-layout .robot-map {
  height: calc(100vh - 60px);
}

@media (max-width: 1100px) {
  .robot-admin-layout {
    flex-direction: column;
  }

  .robot-admin-left,
  .robot-admin-right {
    flex: 1 1 auto;
    width: 100%;
    max-height: none;
  }

  .robot-admin-layout #robotMap,
  .robot-admin-layout .robot-map {
    height: 60vh;
  }
}
</style>

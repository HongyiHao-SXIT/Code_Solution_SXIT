<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import L from 'leaflet'
import { getJson, postJson } from '../lib/api'
import { escapeHtml } from '../lib/escape'
import { focusMapToDenseRegion } from '../lib/mapFocus'
import { pushFlash } from '../stores/session'

const robots = ref([])
const deviceId = ref('')
const name = ref('')
const selectedRobotId = ref(null)
let map = null
let pollTimer = null
let hasAutoFocused = false
let mapInteracted = false
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
    const rawRobots = payload.robots || []
    robots.value = [...rawRobots].sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
    renderMarkers(robots.value)

    if (!mapInteracted && !hasAutoFocused) {
      const focusPoints = robots.value.map((item) => ({ lat: item.lat, lng: item.lng }))
      hasAutoFocused = focusMapToDenseRegion(map, focusPoints, { gridSize: 0.24, maxZoom: 16, singlePointZoom: 15 })
    }
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
    pushFlash('请先在卡片中选择一个机器人', 'warning')
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
  map.on('dragstart zoomstart', () => {
    mapInteracted = true
  })
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
            <div class="robot-card-wrap max-h-520">
              <transition-group name="robot-pop" tag="div" class="robot-card-list">
                <div
                  v-for="robot in robots"
                  :key="robot.id"
                  class="robot-list-card"
                  :class="{ selected: String(robot.id) === selectedRobotId }"
                  @click="setSelectedRobot(robot.id)"
                >
                  <div class="robot-list-card-head">
                    <div class="robot-list-card-name">{{ robot.name || '-' }}</div>
                    <div class="robot-list-card-id">#{{ robot.id }}</div>
                  </div>
                  <div class="robot-list-card-meta">
                    <span class="meta-label">设备ID</span>
                    <span class="meta-value">{{ robot.device_id || '-' }}</span>
                  </div>
                  <div class="robot-list-card-meta">
                    <span class="meta-label">状态</span>
                    <span class="meta-value status-pill">{{ robot.status || '-' }}</span>
                  </div>
                  <div class="robot-list-card-actions">
                    <RouterLink class="btn-operate" :to="`/robot/${robot.id}`">操作</RouterLink>
                    <button type="button" class="btn-delete" @click.stop="deleteRobot(robot.id)">删除</button>
                  </div>
                </div>
              </transition-group>
              <div v-if="!robots.length" class="empty-state">暂无机器人，先添加一个设备</div>
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

.robot-card-wrap {
  display: grid;
  gap: 10px;
}

.robot-card-list {
  display: grid;
  gap: 10px;
}

.robot-list-card {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.robot-list-card:hover {
  transform: translateY(-1px);
  border-color: #87cdb8;
  box-shadow: 0 10px 22px rgba(22, 121, 97, 0.12);
}

.robot-list-card.selected {
  border-color: #10b981;
  box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.35), 0 12px 24px rgba(16, 185, 129, 0.16);
}

.robot-list-card-head,
.robot-list-card-meta,
.robot-list-card-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.robot-list-card-head {
  margin-bottom: 10px;
}

.robot-list-card-name {
  color: #1f2937;
  font-weight: 800;
  font-size: 14px;
}

.robot-list-card-id {
  color: #6b7280;
  font-size: 12px;
}

.robot-list-card-meta {
  margin-bottom: 8px;
}

.meta-label {
  color: #6b7280;
  font-size: 12px;
}

.meta-value {
  color: #1f2937;
  font-size: 13px;
  font-weight: 600;
}

.status-pill {
  border-radius: 999px;
  padding: 2px 10px;
  background: rgba(16, 185, 129, 0.14);
  color: #0d7f5a;
}

.robot-list-card-actions {
  margin-top: 4px;
}

.robot-pop-enter-active {
  animation: robot-card-pop 360ms ease;
}

@keyframes robot-card-pop {
  0% {
    opacity: 0;
    transform: scale(0.92) translateY(10px);
  }
  70% {
    opacity: 1;
    transform: scale(1.02) translateY(-2px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
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

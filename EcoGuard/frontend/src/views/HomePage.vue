<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import * as echarts from 'echarts'
import L from 'leaflet'
import { getJson } from '../lib/api'
import { escapeHtml } from '../lib/escape'
import { pushFlash } from '../stores/session'

const robotList = ref([])
let chart = null
let map = null
let refreshTimer = null
const markers = {}
const taskMarkers = {}

function buildRobotPopup(robot) {
  const batteryText = robot.battery != null ? `${escapeHtml(robot.battery)}%` : ''
  return `<b>${escapeHtml(robot.name || '-')}</b><br>${escapeHtml(robot.device_id || '-')}<br>${escapeHtml(robot.status || '-')} ${batteryText}`
}

function renderTaskPopup(location) {
  const lat = Number(location.lat)
  const lng = Number(location.lng)
  const latText = Number.isFinite(lat) ? lat.toFixed(4) : '-'
  const lngText = Number.isFinite(lng) ? lng.toFixed(4) : '-'
  return `
    <div class="map-popup-card">
      <h4 class="map-popup-title">任务 #${escapeHtml(location.id)}</h4>
      <p class="map-popup-body"><b>识别结果:</b> <span class="map-popup-highlight">${escapeHtml(location.trash_types || '未检测到')}</span></p>
      <p class="map-popup-foot">坐标: ${latText}, ${lngText}</p>
    </div>
  `
}

function syncRobotMarkers(robots) {
  const activeIds = new Set()
  robots.forEach((robot) => {
    if (robot.lat == null || robot.lng == null || !map) {
      return
    }
    const id = String(robot.device_id || robot.id)
    activeIds.add(id)
    if (markers[id]) {
      markers[id].setLatLng([robot.lat, robot.lng])
      markers[id].setPopupContent(buildRobotPopup(robot))
      return
    }
    markers[id] = L.marker([robot.lat, robot.lng], {
      icon: L.divIcon({
        className: 'map-dot-icon',
        html: '<span class="map-dot"></span>',
        iconSize: [14, 14],
        iconAnchor: [7, 7],
      }),
    }).addTo(map).bindPopup(buildRobotPopup(robot))
  })

  Object.keys(markers).forEach((id) => {
    if (activeIds.has(id)) {
      return
    }
    map.removeLayer(markers[id])
    delete markers[id]
  })
}

function syncTaskMarkers(locations) {
  const activeIds = new Set()
  locations.forEach((location) => {
    if (!map || location.lat == null || location.lng == null || location.id == null) {
      return
    }
    const id = String(location.id)
    activeIds.add(id)
    if (taskMarkers[id]) {
      taskMarkers[id].setLatLng([location.lat, location.lng])
      taskMarkers[id].setPopupContent(renderTaskPopup(location))
      return
    }
    taskMarkers[id] = L.marker([location.lat, location.lng], {
      icon: L.divIcon({
        className: 'map-dot-icon',
        html: '<span class="map-dot"></span>',
        iconSize: [14, 14],
        iconAnchor: [7, 7],
      }),
    }).addTo(map)
    taskMarkers[id].bindPopup(renderTaskPopup(location), { closeButton: false, offset: L.point(0, -15) })
    taskMarkers[id].on('mouseover', function onMouseOver() {
      this.openPopup()
    })
    taskMarkers[id].on('mouseout', function onMouseOut() {
      this.closePopup()
    })
  })

  Object.keys(taskMarkers).forEach((id) => {
    if (activeIds.has(id)) {
      return
    }
    map.removeLayer(taskMarkers[id])
    delete taskMarkers[id]
  })
}

function renderPieChart(data) {
  chart?.setOption({
    tooltip: { trigger: 'item' },
    series: [{
      type: 'pie',
      radius: ['40%', '70%'],
      data: data?.length ? data : [{ name: '暂无数据', value: 1 }],
      label: { color: '#2f5b53' },
      itemStyle: { borderRadius: 5 },
    }],
  })
}

async function loadDashboard() {
  try {
    const payload = await getJson('/api/stats/summary')
    robotList.value = payload.robot_list || []
    renderPieChart(payload.pie_data || [])
    syncRobotMarkers(payload.robot_list || [])
    syncTaskMarkers(payload.locations || [])
  } catch (error) {
    pushFlash(error.message || '首页数据加载失败', 'error')
  }
}

onMounted(() => {
  chart = echarts.init(document.getElementById('trashTypeChart'))
  map = L.map('map').setView([30, 110], 5)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)
  loadDashboard()
  refreshTimer = window.setInterval(loadDashboard, 3000)
  window.setTimeout(() => map?.invalidateSize(), 500)
  window.addEventListener('resize', onResize)
})

function onResize() {
  chart?.resize()
  map?.invalidateSize()
}

onBeforeUnmount(() => {
  if (refreshTimer) {
    window.clearInterval(refreshTimer)
  }
  window.removeEventListener('resize', onResize)
  chart?.dispose()
  map?.remove()
})
</script>

<template>
  <div class="layout-content">
    <div class="layout-left">
      <div class="layout-inner-left layout-full-width">
        <div class="panel stats-trash-type">
          <div class="panel-title">垃圾种类分布统计</div>
          <div class="chart-wrap">
            <div id="trashTypeChart" class="chart-fixed-220"></div>
          </div>
        </div>

        <div class="panel robot-status robot-list">
          <div class="panel-title">机器人在线状态</div>
          <div class="chart-wrap">
            <ul class="list-head">
              <li>
                <span>设备ID</span>
                <span>名称</span>
                <span>实时状态</span>
              </li>
            </ul>
            <ul id="robotListContainer" class="list-body">
              <li v-for="robot in robotList" :key="robot.device_id || robot.name" class="list-item">
                <span>{{ robot.device_id }}</span>
                <span>{{ robot.name }}</span>
                <span :class="robot.status === 'ONLINE' ? 'status-online-text' : 'status-offline-text'">
                  {{ robot.status === 'ONLINE' ? '● 在线' : '● 离线' }}
                </span>
              </li>
              <li v-if="!robotList.length" class="list-item"><span>载入中...</span></li>
            </ul>
          </div>
        </div>

        <div class="panel control-pages">
          <div class="panel-title">控制面板</div>
          <div class="chart-wrap">
            <ul class="list-head">
              <li><RouterLink to="/robot"><div class="icon icon-robot"></div><div class="control-title">机器人管理</div></RouterLink></li>
              <li><RouterLink to="/result"><div class="icon icon-task"></div><div class="control-title">任务管理</div></RouterLink></li>
              <li><RouterLink to="/stats"><div class="icon icon-data"></div><div class="control-title">数据分析</div></RouterLink></li>
              <li><RouterLink to="/upload"><div class="icon icon-settings"></div><div class="control-title">手动上传</div></RouterLink></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="layout-center">
      <div class="panel map-main">
        <div class="panel-title">垃圾分布实况地图</div>
        <div class="chart-wrap map-wrap">
          <div class="map-box">
            <div id="map" class="fill-parent"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

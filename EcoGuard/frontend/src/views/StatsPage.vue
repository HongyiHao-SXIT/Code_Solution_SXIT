<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import * as echarts from 'echarts'
import L from 'leaflet'
import { getJson } from '../lib/api'
import { escapeHtml } from '../lib/escape'
import { pushFlash } from '../stores/session'

let pieChart = null
let lineChart = null
let forecastChart = null
let map = null
let actualPointLayer = null
let hotspotLayer = null
const forecastMeta = ref([])
const forecastHotspots = ref([])
const recommendationNotes = ref([])

function asArray(value) {
  return Array.isArray(value) ? value : []
}

function riskColor(score) {
  if (score >= 80) return '#ef4444'
  if (score >= 60) return '#f97316'
  if (score >= 40) return '#facc15'
  return '#22c55e'
}

function updateForecastPanels(payload) {
  const summary = payload?.summary || {}
  forecastMeta.value = [
    { label: '分析网格', value: summary.cells_analyzed || 0 },
    { label: '历史任务', value: summary.tasks_used || 0 },
    { label: '检测目标', value: summary.detections_used || 0 },
  ]

  const hotspots = asArray(payload?.hotspots)
  forecastHotspots.value = hotspots.slice(0, 3).map((item) => {
    const riskScore = Number(item.risk_score || 0)
    return {
      rank: item.rank ?? '-',
      riskScore,
      predictedCount: item.predicted_count ?? '-',
      dominantLabels: asArray(item.dominant_labels).join('、') || '未知',
      displayName: item.display_name || '未知位置',
      reason: item.reason || '',
    }
  })

  recommendationNotes.value = asArray(payload?.recommendations)
    .map((item) => String(item ?? '').trim())
    .filter((item) => item)
}

async function loadStats() {
  try {
    const [summary, hotspots] = await Promise.all([
      getJson('/api/stats/summary'),
      getJson('/api/stats/hotspots'),
    ])

    pieChart.setOption({
      tooltip: {},
      series: [{ type: 'pie', radius: ['40%', '70%'], data: summary.pie_data || [], label: { color: '#2f5b53' } }],
    })

    lineChart.setOption({
      tooltip: { trigger: 'axis' },
      legend: { type: 'scroll', top: 0, textStyle: { color: '#2f5b53' } },
      grid: { left: 48, right: 24, top: 54, bottom: 54 },
      xAxis: { type: 'category', data: summary.line_data?.labels || [], boundaryGap: false, axisLabel: { color: '#2f5b53' } },
      yAxis: { type: 'value', minInterval: 1, axisLabel: { color: '#2f5b53' } },
      series: asArray(summary.line_data?.series).map((item) => ({ name: item.name, type: 'line', smooth: true, data: item.values || [] })),
    })

    forecastChart.setOption({
      tooltip: { trigger: 'axis' },
      grid: { left: 48, right: 24, top: 24, bottom: 36 },
      xAxis: { type: 'category', data: hotspots.chart_data?.labels || [], axisLabel: { color: '#2f5b53' } },
      yAxis: { type: 'value', minInterval: 1, axisLabel: { color: '#2f5b53' } },
      series: [{
        type: 'bar',
        barWidth: '42%',
        data: hotspots.chart_data?.values || [],
        itemStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: '#fb7185' },
            { offset: 1, color: '#f97316' },
          ]),
          borderRadius: [6, 6, 0, 0],
        },
      }],
    })

    updateForecastPanels(hotspots)

    actualPointLayer.clearLayers()
    asArray(summary.locations).forEach((location) => {
      if (location.lat == null || location.lng == null) return
      L.marker([location.lat, location.lng], {
        icon: L.divIcon({ className: 'map-dot-icon', html: '<span class="map-dot"></span>', iconSize: [10, 10], iconAnchor: [5, 5] }),
      }).addTo(actualPointLayer).bindPopup(`<b>任务 ${escapeHtml(location.id)}</b><br>${escapeHtml(location.trash_types)}`)
    })

    hotspotLayer.clearLayers()
    asArray(hotspots.hotspots).forEach((item) => {
      if (item.center_lat == null || item.center_lng == null) return
      L.circleMarker([item.center_lat, item.center_lng], {
        radius: 10,
        color: riskColor(item.risk_score),
        fillColor: riskColor(item.risk_score),
        fillOpacity: 0.35,
      }).addTo(hotspotLayer).bindPopup(`TOP ${escapeHtml(item.rank)}<br>${escapeHtml(item.display_name || '未知位置')}`)
    })
  } catch (error) {
    pushFlash(error.message || '统计数据加载失败', 'error')
  }
}

function onResize() {
  pieChart?.resize()
  lineChart?.resize()
  forecastChart?.resize()
  map?.invalidateSize()
}

onMounted(() => {
  pieChart = echarts.init(document.getElementById('pieChart'))
  lineChart = echarts.init(document.getElementById('lineChart'))
  forecastChart = echarts.init(document.getElementById('forecastChart'))
  map = L.map('statsMap').setView([30, 110], 5)
  actualPointLayer = L.layerGroup().addTo(map)
  hotspotLayer = L.layerGroup().addTo(map)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map)
  loadStats()
  window.setTimeout(() => map?.invalidateSize(), 500)
  window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
  pieChart?.dispose()
  lineChart?.dispose()
  forecastChart?.dispose()
  map?.remove()
})
</script>

<template>
  <div class="panel page-panel">
    <div class="panel-title">数据分析</div>
    <div class="panel-body">
      <div class="stats-grid">
        <div class="stats-card"><div class="section-title section-title-sm">垃圾种类分布</div><div id="pieChart" class="chart-fixed-260"></div></div>
        <div class="stats-card"><div class="section-title section-title-sm">全部识别趋势</div><div id="lineChart" class="chart-fixed-260"></div></div>
        <div class="stats-card"><div class="section-title section-title-sm">未来 24 小时热点预测</div><div id="forecastChart" class="chart-fixed-260"></div></div>
        <div class="stats-card">
          <div class="section-title section-title-sm">巡检建议</div>
          <div class="forecast-meta">
            <div v-for="item in forecastMeta" :key="item.label" class="forecast-meta-item">
              <span>{{ item.label }}</span>
              <strong>{{ item.value }}</strong>
            </div>
          </div>
          <div class="forecast-list">
            <div v-for="item in forecastHotspots" :key="`hotspot-${item.rank}`" class="forecast-list-item">
              <div class="forecast-list-head">
                <span class="forecast-rank">TOP {{ item.rank }}</span>
                <span class="forecast-risk" :style="{ background: riskColor(item.riskScore) }">风险 {{ item.riskScore }}</span>
              </div>
              <div class="forecast-list-main">预测目标数 {{ item.predictedCount }}，主导类型：{{ item.dominantLabels }}</div>
              <div class="forecast-list-sub">位置：{{ item.displayName }}</div>
              <div class="forecast-list-sub">{{ item.reason }}</div>
            </div>
            <div v-for="(item, index) in recommendationNotes" :key="`note-${index}`" class="forecast-list-note">{{ item }}</div>
            <div v-if="!forecastHotspots.length && !recommendationNotes.length" class="forecast-list-note">暂无巡检建议</div>
          </div>
        </div>
      </div>
      <div class="stats-map-wrap">
        <div class="section-title section-title-sm stats-map-title">实际点位与预测热点</div>
        <div id="statsMap" class="stats-map"></div>
      </div>
    </div>
  </div>
</template>

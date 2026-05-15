<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getJson } from '../lib/api'
import { confirmAndDeleteTask } from '../composables/useResultTaskActions'
import { pushFlash } from '../stores/session'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const rows = ref([])
const pagination = ref(null)
const canDelete = ref(false)

const viewMode = computed(() => (route.query.mode === 'items' ? 'items' : 'tasks'))
const currentPage = computed(() => Number(route.query.page || 1))
const isTaskView = computed(() => viewMode.value === 'tasks')
const pageTitle = computed(() => (isTaskView.value ? '任务视图' : '检测项视图'))

function buildListEndpoint(mode, page) {
  return mode === 'tasks'
    ? `/api/web/tasks?page=${page}`
    : `/api/web/items?page=${page}`
}

function extractRows(payload, mode) {
  return mode === 'tasks' ? (payload.tasks || []) : (payload.items || [])
}

function buildRouteTarget(mode, page) {
  return { name: 'results', query: { mode, page } }
}

function formatConfidence(value) {
  if (value == null || value === '') {
    return '-'
  }
  return Number(value).toFixed(3)
}

async function loadRows() {
  loading.value = true
  try {
    const endpoint = buildListEndpoint(viewMode.value, currentPage.value)
    const payload = await getJson(endpoint)
    rows.value = extractRows(payload, viewMode.value)
    pagination.value = payload.pagination || null
    canDelete.value = Boolean(payload.can_delete)
  } catch (error) {
    pushFlash(error.message || `${pageTitle.value}加载失败`, 'error')
  } finally {
    loading.value = false
  }
}

async function deleteTask(taskId) {
  await confirmAndDeleteTask(taskId, {
    onSuccess: loadRows,
  })
}

function goPage(page) {
  router.push(buildRouteTarget(viewMode.value, page))
}

function switchMode(mode) {
  if (mode === viewMode.value) {
    return
  }
  router.push(buildRouteTarget(mode, 1))
}

watch(() => [route.query.page, route.query.mode], loadRows)
onMounted(loadRows)
</script>

<template>
  <div class="panel page-panel result-list-page">
    <div class="panel-title">结果列表</div>
    <div class="panel-body">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div class="section-title section-title-sm" style="margin:0;">{{ pageTitle }}</div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button
            type="button"
            class="btn-detail result-list-square-btn"
            :style="isTaskView ? '' : 'opacity:0.72;'"
            @click="switchMode('tasks')"
          >
            任务视图
          </button>
          <button
            type="button"
            class="btn-detail result-list-square-btn"
            :style="!isTaskView ? '' : 'opacity:0.72;'"
            @click="switchMode('items')"
          >
            检测项视图
          </button>
        </div>
      </div>
      <div v-if="loading" class="page-loading">载入中...</div>
      <template v-else>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr v-if="isTaskView">
                <th>ID</th>
                <th>来源</th>
                <th>设备ID</th>
                <th>状态</th>
                <th>位置</th>
                <th>时间</th>
                <th class="col-center">操作</th>
              </tr>
              <tr v-else>
                <th>ID</th>
                <th>任务ID</th>
                <th>类别</th>
                <th>置信度</th>
                <th>来源</th>
                <th>设备ID</th>
                <th>状态</th>
                <th>位置</th>
                <th>时间</th>
                <th class="col-center">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isTaskView" v-for="task in rows" :key="`task-${task.id}`">
                <td>{{ task.id }}</td>
                <td>{{ task.source_type || '-' }}</td>
                <td>{{ task.device_id || '-' }}</td>
                <td>{{ task.status || '-' }}</td>
                <td>{{ task.display_location || task.location || '-' }}</td>
                <td>{{ task.created_at || '-' }}</td>
                <td class="col-center col-nowrap">
                  <RouterLink class="btn-detail result-list-square-btn" :to="`/result/${task.id}`">详情</RouterLink>
                  <button v-if="canDelete" type="button" class="btn-delete" style="margin-left:6px;" @click="deleteTask(task.id)">删除任务</button>
                </td>
              </tr>
              <tr v-else v-for="item in rows" :key="`item-${item.id}`">
                <td>{{ item.id }}</td>
                <td>{{ item.task_id }}</td>
                <td>{{ item.label || '-' }}</td>
                <td>{{ formatConfidence(item.confidence) }}</td>
                <td>{{ item.source_type || '-' }}</td>
                <td>{{ item.device_id || '-' }}</td>
                <td>{{ item.task_status || '-' }}</td>
                <td>{{ item.display_location }}</td>
                <td>{{ item.captured_at || item.task_created_at || '-' }}</td>
                <td class="col-center col-nowrap">
                  <RouterLink class="btn-detail result-list-square-btn" :to="`/result/${item.task_id}`">任务详情</RouterLink>
                  <button v-if="canDelete" type="button" class="btn-delete" style="margin-left:6px;" @click="deleteTask(item.task_id)">删除任务</button>
                </td>
              </tr>
              <tr v-if="!rows.length">
                <td :colspan="isTaskView ? 7 : 10" class="empty-state">暂无{{ isTaskView ? '任务' : '检测项' }}数据</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="pagination" class="pagination-line">
          <a v-if="pagination.has_prev" class="link-inline" href="#" @click.prevent="goPage(pagination.prev_num)">上一页</a>
          &nbsp; 第 {{ pagination.page }} 页 &nbsp;
          <a v-if="pagination.has_next" class="link-inline" href="#" @click.prevent="goPage(pagination.next_num)">下一页</a>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.result-list-page .result-list-square-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  padding: 0 12px;
  border-radius: 6px;
  text-decoration: none;
}
</style>

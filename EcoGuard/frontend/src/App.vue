<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { postJson } from './lib/api'
import { clearSessionUser, pushFlash, sessionState } from './stores/session'

const route = useRoute()
const router = useRouter()
const headerDate = ref('')
const headerWeek = ref('')
const navItems = [
  { to: '/', label: '首页', match: ['/'] },
  { to: '/robot', label: '机器人管理', match: ['/robot'] },
  { to: '/result', label: '检测结果', match: ['/result'] },
  { to: '/stats', label: '统计分析', match: ['/stats'] },
  { to: '/train', label: '继续训练', match: ['/train'] },
]
let timerId = null

const authUser = computed(() => sessionState.user)

function isActive(matchers) {
  return matchers.some((item) => route.path === item || route.path.startsWith(`${item}/`))
}

function renderHeaderTime() {
  const now = new Date()
  headerDate.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`
  headerWeek.value = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'][now.getDay()]
}

async function logout() {
  try {
    await postJson('/api/web/logout', {})
    clearSessionUser()
    pushFlash('已退出登录', 'success')
    await router.replace('/login')
  } catch (error) {
    pushFlash(error.message || '退出失败', 'error')
  }
}

onMounted(() => {
  renderHeaderTime()
  timerId = window.setInterval(renderHeaderTime, 1000)
})

onBeforeUnmount(() => {
  if (timerId) {
    window.clearInterval(timerId)
  }
})
</script>

<template>
  <div class="layout-main">
    <div class="layout-header">
      <div class="layout-header-left">
        <div class="layout-brand-badge">EG</div>
        <div class="layout-nav">
          <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="layout-nav-link"
            :class="{ active: isActive(item.match) }"
          >
            {{ item.label }}
          </RouterLink>
        </div>
      </div>
      <div class="layout-header-center">
        <div class="layout-header-title">TrashDet 垃圾拾捡机器人管理系统</div>
        <div class="layout-header-subtitle">实时识别 · 机器人调度 · 热点预警</div>
      </div>
      <div class="layout-header-right">
        <div class="layout-user-row">
          <template v-if="authUser">
            <span class="layout-user-label">用户：{{ authUser.username }}</span>
            <button
              type="button"
              class="layout-logout-btn"
              @click="logout"
            >
              退出
            </button>
          </template>
          <template v-else>
            <RouterLink to="/login" class="layout-auth-link">登录</RouterLink>
            <RouterLink to="/register" class="layout-auth-link">注册</RouterLink>
          </template>
        </div>
        <div class="header-date">{{ headerDate }}</div>
        <div class="header-week">{{ headerWeek }}</div>
      </div>
    </div>

    <div class="layout-page-container">
      <RouterView />
    </div>
  </div>
</template>

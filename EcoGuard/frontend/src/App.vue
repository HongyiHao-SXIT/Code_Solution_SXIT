<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { postJson } from './lib/api'
import { clearSessionUser, sessionState } from './stores/session'

const route = useRoute()
const router = useRouter()
const authUser = computed(() => sessionState.user)
const isAuthPage = computed(() => Boolean(route.meta?.guestOnly))

const NAV_ITEMS = [
  { to: '/', label: '概览', icon: 'dashboard' },
  { to: '/robot', label: '机器人', icon: 'robot' },
  { to: '/result', label: '检测结果', icon: 'task' },
  { to: '/stats', label: '数据分析', icon: 'chart' },
  { to: '/train', label: '模型训练', icon: 'train' },
  { to: '/users', label: '个人中心', icon: 'user' },
]

function isActive(path) {
  if (path === '/') return route.path === '/'
  // 精确前缀匹配：/result 不会误匹配 /result-detail
  return route.path === path || route.path.startsWith(path + '/')
}

async function logout() {
  try {
    await postJson('/api/web/logout', {})
  } catch {
    // 静默处理：后端失败不影响前端登出
  }
  clearSessionUser()
  await router.replace('/login')
}
</script>

<template>
  <!-- Auth pages have their own clean layout -->
  <div v-if="isAuthPage" class="auth-layout">
    <RouterView />
  </div>

  <!-- Main app with sidebar -->
  <div v-else class="app-shell">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <RouterLink to="/" class="sidebar-logo">
          <svg class="logo-icon" viewBox="0 0 32 32" fill="none">
            <path d="M16 2C10.48 2 6 6.48 6 12v4c0 1.1.9 2 2 2h2v8c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-8h2c1.1 0 2-.9 2-2v-4c0-5.52-4.48-10-10-10z" fill="currentColor" opacity=".15"/>
            <path d="M16 3c-2.67 0-5.12 1.04-7 2.75V7h-1c-1.1 0-2 .9-2 2v3h2v-3h1v2h2V7h2v2h2V7h2v2h2V7h2v2h2V7h1v3h2V9c0-1.1-.9-2-2-2h-1V5.75A9.96 9.96 0 0116 3z" fill="currentColor" opacity=".35"/>
            <path d="M12 18h8v8h-8z" fill="currentColor"/>
            <rect x="9" y="10" width="14" height="4" rx="1" fill="currentColor"/>
            <circle cx="16" cy="12" r="1" fill="#0a0e14"/>
          </svg>
          <span class="brand-text">EcoGuard</span>
        </RouterLink>
      </div>

      <nav class="sidebar-nav">
        <RouterLink
          v-for="item in NAV_ITEMS"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          :class="{ active: isActive(item.to) }"
        >
          <!-- Inline SVG icons -->
          <svg v-if="item.icon === 'dashboard'" class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zm10 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
          </svg>
          <svg v-else-if="item.icon === 'robot'" class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 2a2 2 0 00-2 2v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-3V4a2 2 0 00-2-2zM8 4a2 2 0 014 0zm-1 7a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/>
          </svg>
          <svg v-else-if="item.icon === 'task'" class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
          </svg>
          <svg v-else-if="item.icon === 'chart'" class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
          </svg>
          <svg v-else-if="item.icon === 'train'" class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
          </svg>
          <svg v-else class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
          </svg>
          <span class="nav-label">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <template v-if="authUser">
          <div class="sidebar-user">
            <div class="user-avatar">{{ authUser.username?.charAt(0)?.toUpperCase() || 'U' }}</div>
            <div class="user-info">
              <div class="user-name">{{ authUser.username }}</div>
              <div class="user-role">{{ authUser.role === 'admin' ? '管理员' : '用户' }}</div>
            </div>
          </div>
          <button class="sidebar-logout" @click="logout" title="退出登录">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
              <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h5a1 1 0 100-2H4V5h4a1 1 0 100-2H3zm11.707 3.293a1 1 0 010 1.414L12.414 10l2.293 2.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
          </button>
        </template>
        <template v-else>
          <div class="sidebar-auth-links">
            <RouterLink to="/login" class="nav-item">登录</RouterLink>
            <RouterLink to="/register" class="nav-item">注册</RouterLink>
          </div>
        </template>
      </div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
/* ===== Auth Layout ===== */
.auth-layout {
  width: 100%;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(ellipse at 20% 50%, rgba(34, 197, 94, 0.06) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(59, 130, 246, 0.04) 0%, transparent 50%),
    var(--color-bg-base);
  padding: var(--space-xl);
}

/* ===== App Shell ===== */
.app-shell {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
}

/* ===== Sidebar ===== */
.sidebar {
  width: var(--sidebar-width);
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: var(--color-bg-elevated);
  border-right: 1px solid var(--color-border);
  overflow-y: auto;
  overflow-x: hidden;
}

.sidebar-brand {
  padding: var(--space-lg) var(--space-md);
  border-bottom: 1px solid var(--color-border);
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  text-decoration: none;
  color: var(--color-text-primary);
}

.logo-icon {
  width: 32px;
  height: 32px;
  color: var(--color-accent);
  flex-shrink: 0;
}

.brand-text {
  font-size: var(--font-size-xl);
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--color-text-primary);
}

/* Navigation */
.sidebar-nav {
  flex: 1;
  padding: var(--space-sm);
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: 10px var(--space-md);
  font-size: var(--font-size-sm);
  font-weight: 500;
  color: var(--color-text-secondary);
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.nav-item:hover {
  color: var(--color-text-primary);
  background: var(--color-bg-hover);
}

.nav-item.active {
  color: var(--color-accent);
  background: var(--color-accent-soft);
  font-weight: 600;
}

.nav-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  opacity: 0.7;
}

.nav-item.active .nav-icon {
  opacity: 1;
}

.nav-label {
  white-space: nowrap;
}

/* Sidebar Footer */
.sidebar-footer {
  padding: var(--space-md);
  border-top: 1px solid var(--color-border);
}

.sidebar-user {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm);
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-full);
  background: var(--color-accent);
  color: var(--color-text-inverse);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-base);
  font-weight: 700;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--color-text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-role {
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
}

.sidebar-logout {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  background: transparent;
  transition: all var(--transition-fast);
  flex-shrink: 0;
}

.sidebar-logout:hover {
  color: var(--color-danger);
  background: var(--color-danger-soft);
  transform: none;
  box-shadow: none;
}

.sidebar-auth-links {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

/* ===== Main Content ===== */
.main-content {
  flex: 1;
  min-width: 0;
  overflow-y: auto;
  overflow-x: hidden;
  background: var(--color-bg-base);
}

@media (max-width: 768px) {
  .sidebar {
    display: none;
  }
}
</style>
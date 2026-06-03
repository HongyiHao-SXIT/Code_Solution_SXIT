<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import UserProfileFields from '../components/UserProfileFields.vue'
import { getJson, postJson } from '../lib/api'
import { validateUserProfileForm } from '../lib/formValidation'
import { pushFlash, sessionState, setSessionUser } from '../stores/session'

const loading = ref(false)
const submitting = ref(false)
const updatingUserId = ref(null)
const deletingUserId = ref(null)
const editingUserId = ref(null)
const savingProfileUserId = ref(null)
const selfAssetsLoading = ref(false)
const robotOpsLoadingId = ref(null)
const patrolOpsLoadingId = ref(null)
const editingRowName = ref('')
const rows = ref([])
const myRobots = ref([])
const myPatrolTasks = ref([])
const loadError = ref('')
const canManage = ref(false)
const selfSubmitting = ref(false)
const summary = ref({
  total: 0,
  admin_count: 0,
  user_count: 0,
})

const createForm = reactive({
  username: '',
  organization: '',
  password: '',
  confirm_password: '',
  security_code: '',
  role: 'user',
})

const editForm = reactive({
  username: '',
  organization: '',
  password: '',
  confirm_password: '',
})

const selfForm = reactive({
  username: '',
  organization: '',
  password: '',
  confirm_password: '',
})

const authUser = computed(() => sessionState.user)
const hasRows = computed(() => rows.value.length > 0)
const currentUserRow = computed(() => rows.value.find((row) => row.is_current_user) || rows.value[0] || null)
const currentUsername = computed(() => {
  const candidate = String(
    currentUserRow.value?.username || selfForm.username || authUser.value?.username || '',
  ).trim()
  return candidate || '-'
})
const currentRoleText = computed(() => (authUser.value?.role === 'admin' ? '管理员' : '普通用户'))
const pageTitle = computed(() => (canManage.value ? '账号与权限管理' : '个人信息管理'))
const pageSubtitle = computed(() => (
  canManage.value
    ? '管理员模式：可查看并管理所有用户（接口：/api/web/admin/users）'
    : '普通用户模式：仅管理当前登录账号信息'
))
const hasMyRobots = computed(() => myRobots.value.length > 0)
const hasMyPatrolTasks = computed(() => myPatrolTasks.value.length > 0)

function resetSummary() {
  summary.value = { total: 0, admin_count: 0, user_count: 0 }
}

function roleText(role) {
  return role === 'admin' ? '管理员' : '普通用户'
}

function patrolStatusText(status) {
  const code = String(status || '').toUpperCase()
  if (code === 'PLANNED') return '待执行'
  if (code === 'RUNNING') return '执行中'
  if (code === 'PAUSED') return '已暂停'
  if (code === 'DONE') return '已完成'
  if (code === 'CANCELLED') return '已取消'
  return status || '-'
}

function formatDateTime(value) {
  if (!value) return '-'
  const dt = new Date(value)
  if (Number.isNaN(dt.getTime())) return String(value)
  return dt.toLocaleString('zh-CN', { hour12: false })
}

function resetCreateForm() {
  createForm.username = ''
  createForm.organization = ''
  createForm.password = ''
  createForm.confirm_password = ''
  createForm.security_code = ''
  createForm.role = 'user'
}

function resetSelfForm() {
  selfForm.username = ''
  selfForm.organization = ''
  selfForm.password = ''
  selfForm.confirm_password = ''
}

function syncSelfFormFromRow(row) {
  if (!row) {
    resetSelfForm()
    return
  }
  selfForm.username = row.username || ''
  selfForm.organization = row.organization || ''
  selfForm.password = ''
  selfForm.confirm_password = ''
}

async function loadSelfAssets() {
  selfAssetsLoading.value = true
  try {
    const [robotPayload, taskPayload] = await Promise.all([
      getJson('/api/robot/list'),
      getJson('/api/robot/task/list'),
    ])
    myRobots.value = [...(robotPayload.robots || [])].sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
    myPatrolTasks.value = [...(taskPayload.tasks || [])].sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
  } catch (error) {
    myRobots.value = []
    myPatrolTasks.value = []
    pushFlash(error.message || '加载个人资产失败', 'error')
  } finally {
    selfAssetsLoading.value = false
  }
}

async function loadUsers() {
  loading.value = true
  loadError.value = ''
  try {
    const payload = await getJson('/api/web/admin/users')
    canManage.value = Boolean(payload.can_manage)
    rows.value = [...(payload.users || [])].sort((a, b) => Number(a.id || 0) - Number(b.id || 0))
    summary.value = payload.summary || { total: 0, admin_count: 0, user_count: 0 }
    editingUserId.value = null
    if (!canManage.value) {
      syncSelfFormFromRow(rows.value[0] || null)
      await loadSelfAssets()
    } else {
      myRobots.value = []
      myPatrolTasks.value = []
    }
  } catch (error) {
    rows.value = []
    canManage.value = false
    resetSummary()
    if (error?.status === 401) {
      loadError.value = '当前登录已失效，请重新登录后再加载用户数据。'
    } else {
      loadError.value = error.message || '用户列表加载失败，请检查后端服务和数据库连接。'
    }
    pushFlash(error.message || '用户列表加载失败', 'error')
  } finally {
    loading.value = false
  }
}

async function deleteMyRobot(robot) {
  if (!robot?.id) return
  if (!window.confirm(`确认删除机器人 ${robot.name || robot.device_id || `#${robot.id}`} 吗？`)) return

  robotOpsLoadingId.value = robot.id
  try {
    await postJson(`/api/robot/delete/${robot.id}`, {})
    pushFlash('机器人已删除', 'success')
    await loadUsers()
  } catch (error) {
    pushFlash(error.message || '删除机器人失败', 'error')
  } finally {
    robotOpsLoadingId.value = null
  }
}

async function setMyPatrolTaskStatus(task, status) {
  if (!task?.id) return
  patrolOpsLoadingId.value = task.id
  try {
    await postJson(`/api/robot/task/update/${task.id}`, { status })
    pushFlash(`任务状态已更新为${patrolStatusText(status)}`, 'success')
    await loadSelfAssets()
  } catch (error) {
    pushFlash(error.message || '更新任务状态失败', 'error')
  } finally {
    patrolOpsLoadingId.value = null
  }
}

async function deleteMyPatrolTask(task) {
  if (!task?.id) return
  if (!window.confirm(`确认删除巡检任务 ${task.name || `#${task.id}`} 吗？`)) return

  patrolOpsLoadingId.value = task.id
  try {
    await postJson(`/api/robot/task/delete/${task.id}`, {})
    pushFlash('巡检任务已删除', 'success')
    await loadSelfAssets()
  } catch (error) {
    pushFlash(error.message || '删除巡检任务失败', 'error')
  } finally {
    patrolOpsLoadingId.value = null
  }
}

async function saveCurrentUserProfile() {
  if (canManage.value) {
    pushFlash('管理员请在用户列表中编辑账号信息', 'warning')
    return
  }

  const validationError = validateUserProfileForm(selfForm, {
    requirePassword: false,
    allowEmptyPassword: true,
  })
  if (validationError) {
    pushFlash(validationError, 'warning')
    return
  }

  selfSubmitting.value = true
  try {
    const payload = await postJson('/api/web/users/me/update', {
      username: selfForm.username,
      organization: selfForm.organization,
      password: selfForm.password,
      confirm_password: selfForm.confirm_password,
    })
    if (payload?.user) {
      setSessionUser(payload.user)
    }
    pushFlash('个人信息已更新', 'success')
    await loadUsers()
  } catch (error) {
    pushFlash(error.message || '更新个人信息失败', 'error')
  } finally {
    selfSubmitting.value = false
  }
}

async function createUser() {
  if (!canManage.value) {
    pushFlash('当前账号只有查看权限，无法创建用户', 'warning')
    return
  }

  const validationError = validateUserProfileForm(createForm, {
    requireSecurityCode: true,
    requirePassword: true,
    requireRole: true,
  })
  if (validationError) {
    pushFlash(validationError, 'warning')
    return
  }

  submitting.value = true
  try {
    await postJson('/api/web/admin/users', {
      username: createForm.username,
      organization: createForm.organization,
      password: createForm.password,
      confirm_password: createForm.confirm_password,
      security_code: createForm.security_code,
      role: createForm.role,
    })
    pushFlash('用户创建成功', 'success')
    resetCreateForm()
    await loadUsers()
  } catch (error) {
    pushFlash(error.message || '创建用户失败', 'error')
  } finally {
    submitting.value = false
  }
}

async function toggleRole(row) {
  if (!canManage.value) {
    pushFlash('当前账号只有查看权限，无法修改角色', 'warning')
    return
  }
  const nextRole = row.role === 'admin' ? 'user' : 'admin'
  updatingUserId.value = row.id
  try {
    await postJson(`/api/web/admin/users/${row.id}/role`, { role: nextRole })
    pushFlash('用户角色已更新', 'success')
    await loadUsers()
  } catch (error) {
    pushFlash(error.message || '更新用户角色失败', 'error')
  } finally {
    updatingUserId.value = null
  }
}

function startEdit(row) {
  if (!canManage.value) {
    pushFlash('当前账号只有查看权限，无法编辑用户信息', 'warning')
    return
  }
  if (row.is_current_user) {
    pushFlash('请在个人中心修改当前登录用户信息', 'warning')
    return
  }

  editingUserId.value = row.id
  editingRowName.value = row.username || ''
  editForm.username = row.username || ''
  editForm.organization = row.organization || ''
  editForm.password = ''
  editForm.confirm_password = ''
}

function cancelEdit() {
  editingUserId.value = null
  editingRowName.value = ''
  editForm.username = ''
  editForm.organization = ''
  editForm.password = ''
  editForm.confirm_password = ''
}

async function saveUserProfile() {
  if (!canManage.value) {
    pushFlash('当前账号只有查看权限，无法编辑用户信息', 'warning')
    return
  }

  const targetUserId = editingUserId.value
  if (!targetUserId) {
    return
  }

  const validationError = validateUserProfileForm(editForm, {
    requirePassword: false,
    allowEmptyPassword: true,
  })
  if (validationError) {
    pushFlash(validationError, 'warning')
    return
  }

  savingProfileUserId.value = targetUserId
  try {
    await postJson(`/api/web/admin/users/${targetUserId}/update`, {
      username: editForm.username,
      organization: editForm.organization,
      password: editForm.password,
      confirm_password: editForm.confirm_password,
    })
    pushFlash('用户信息已更新', 'success')
    cancelEdit()
    await loadUsers()
  } catch (error) {
    pushFlash(error.message || '更新用户信息失败', 'error')
  } finally {
    savingProfileUserId.value = null
  }
}

async function removeUser(row) {
  if (!canManage.value) {
    pushFlash('当前账号只有查看权限，无法删除用户', 'warning')
    return
  }
  if (!window.confirm(`确认删除用户 ${row.username} 吗？`)) {
    return
  }

  deletingUserId.value = row.id
  try {
    await postJson(`/api/web/admin/users/${row.id}/delete`, {})
    pushFlash('用户删除成功', 'success')
    await loadUsers()
  } catch (error) {
    pushFlash(error.message || '删除用户失败', 'error')
  } finally {
    deletingUserId.value = null
  }
}

onMounted(loadUsers)
</script>

<template>
  <div class="panel page-panel user-admin-page">
    <div class="panel-title">用户管理 · User Admin</div>
    <div class="panel-body user-admin-body">
      <section class="user-page-head" aria-label="页面头部">
        <div>
          <h3 class="section-title">{{ pageTitle }}</h3>
          <p class="user-page-subtitle">{{ pageSubtitle }}</p>
        </div>
        <div class="head-right">
          <span class="login-badge">当前用户：{{ currentUsername }}</span>
          <span class="login-badge">当前身份：{{ currentRoleText }}</span>
          <button type="button" class="btn-detail" @click="loadUsers">刷新数据</button>
        </div>
      </section>

      <div v-if="loadError" class="error-banner">
        <strong>加载失败：</strong>{{ loadError }}
      </div>

      <section v-if="canManage" class="user-summary" aria-label="用户摘要">
        <div class="summary-item">
          <div class="summary-label">用户总数</div>
          <div class="summary-value">{{ summary.total }}</div>
        </div>
        <div class="summary-item">
          <div class="summary-label">管理员</div>
          <div class="summary-value">{{ summary.admin_count }}</div>
        </div>
        <div class="summary-item">
          <div class="summary-label">普通用户</div>
          <div class="summary-value">{{ summary.user_count }}</div>
        </div>
      </section>

      <section v-if="canManage" class="user-main-grid" aria-label="用户管理主体">
        <div class="user-create card-surface" aria-label="创建用户">
          <h3 class="section-title section-title-sm">创建新用户</h3>
          <form class="create-grid" @submit.prevent="createUser">
            <UserProfileFields :form="createForm" :include-security-code="true" :include-role="true"
              :require-password="true" />
            <div class="create-actions">
              <button type="submit" :disabled="submitting">
                {{ submitting ? '创建中...' : '创建用户' }}
              </button>
            </div>
          </form>
        </div>

        <div class="user-list card-surface" aria-label="用户列表">
          <h3 class="section-title section-title-sm">用户列表</h3>
          <div v-if="loading" class="page-loading">载入中...</div>
          <div v-else-if="!hasRows" class="empty-state">暂无用户数据（后端 user 表）</div>
          <div v-else class="user-list-shell">
            <div class="user-list-head">
              <span>用户</span>
              <span>所属单位</span>
              <span>角色</span>
              <span class="col-right">操作</span>
            </div>
            <transition-group name="user-row-pop" tag="ul" class="user-list-rows">
              <li v-for="row in rows" :key="row.id" class="user-list-row">
                <div class="user-col user-col-user">
                  <div class="user-mainline">
                    <span class="user-avatar">{{ row.username ? row.username.slice(0, 1).toUpperCase() : '?' }}</span>
                    <div class="user-texts">
                      <div class="user-item-name">
                        {{ row.username }}
                        <span v-if="row.is_current_user" class="current-tag">当前登录</span>
                      </div>
                      <span class="user-item-id">ID #{{ row.id }}</span>
                    </div>
                  </div>
                </div>

                <div class="user-col user-col-role">
                  <span class="organization-tag">{{ row.organization || '-' }}</span>
                </div>

                <div class="user-col user-col-role">
                  <span :class="row.role === 'admin' ? 'role-admin' : 'role-user'">
                    {{ roleText(row.role) }}
                  </span>
                </div>

                <div class="user-col user-col-actions">
                  <button v-if="canManage" type="button" class="btn-detail"
                    :disabled="row.is_current_user || editingUserId !== null" @click="startEdit(row)">
                    编辑信息
                  </button>
                  <button v-if="canManage" type="button" class="btn-detail"
                    :disabled="updatingUserId === row.id || editingUserId !== null || (row.is_current_user && row.role === 'admin')"
                    @click="toggleRole(row)">
                    {{ updatingUserId === row.id ? '更新中...' : (row.role === 'admin' ? '降为普通用户' : '设为管理员') }}
                  </button>
                  <button v-if="canManage" type="button" class="btn-delete"
                    :disabled="deletingUserId === row.id || editingUserId !== null || row.is_current_user"
                    @click="removeUser(row)">
                    {{ deletingUserId === row.id ? '删除中...' : '删除' }}
                  </button>
                </div>
              </li>
            </transition-group>
          </div>
        </div>
      </section>

      <section v-else class="user-self-layout" aria-label="个人中心主体">
        <div class="card-surface self-profile-card">
          <div class="profile-card-head">
            <span class="profile-avatar">{{ currentUsername.slice(0, 1).toUpperCase() }}</span>
            <div class="profile-head-texts">
              <h3 class="section-title section-title-sm">个人信息卡</h3>
              <div class="profile-username">{{ currentUsername }}</div>
              <div class="profile-meta">
                <span class="profile-tag">{{ currentRoleText }}</span>
                <span class="profile-tag">{{ currentUserRow?.organization || '未填写单位' }}</span>
              </div>
            </div>
          </div>

          <p class="user-page-subtitle" style="margin-top: 0;">
            仅管理当前登录账号信息，保存后即时生效。
          </p>

          <form class="self-profile-form" @submit.prevent="saveCurrentUserProfile">
            <UserProfileFields
              :form="selfForm"
              password-label="新密码（留空不修改）"
              confirm-password-label="确认新密码"
              password-placeholder="至少 6 位"
              confirm-password-placeholder="再次输入新密码"
            />
            <div class="create-actions">
              <button type="submit" :disabled="selfSubmitting">
                {{ selfSubmitting ? '保存中...' : '保存我的信息' }}
              </button>
            </div>
          </form>
        </div>

        <div class="card-surface self-assets-card">
          <div class="assets-head">
            <h3 class="section-title section-title-sm">我的机器人与任务</h3>
            <button type="button" class="self-square-btn" :disabled="selfAssetsLoading" @click="loadSelfAssets">
              {{ selfAssetsLoading ? '刷新中...' : '刷新' }}
            </button>
          </div>

          <div class="self-overview-grid" v-if="currentUserRow">
            <div class="overview-item">
              <span class="overview-label">用户 ID</span>
              <span class="overview-value">#{{ currentUserRow.id }}</span>
            </div>
            <div class="overview-item">
              <span class="overview-label">用户名</span>
              <span class="overview-value">{{ currentUserRow.username || '-' }}</span>
            </div>
          </div>

          <div class="asset-section">
            <div class="asset-section-head">
              <h4>我的机器人</h4>
              <RouterLink to="/robot" class="self-square-btn">打开机器人管理</RouterLink>
            </div>
            <div v-if="selfAssetsLoading" class="page-loading">载入中...</div>
            <div v-else-if="!hasMyRobots" class="empty-state">你还没有绑定机器人</div>
            <ul v-else class="asset-list">
              <li v-for="robot in myRobots" :key="`robot-${robot.id}`" class="asset-item">
                <div class="asset-main">
                  <div class="asset-title">{{ robot.name || '未命名机器人' }}</div>
                  <div class="asset-meta">设备ID：{{ robot.device_id || '-' }} · 状态：{{ robot.status || '-' }}</div>
                </div>
                <div class="asset-actions">
                  <RouterLink class="self-square-btn" :to="`/robot/${robot.id}`">进入控制</RouterLink>
                  <button
                    type="button"
                    class="self-square-btn self-square-btn-danger"
                    :disabled="robotOpsLoadingId === robot.id"
                    @click="deleteMyRobot(robot)">
                    {{ robotOpsLoadingId === robot.id ? '删除中...' : '删除' }}
                  </button>
                </div>
              </li>
            </ul>
          </div>

          <div class="asset-section">
            <div class="asset-section-head">
              <h4>我的巡检任务</h4>
              <RouterLink to="/result" class="self-square-btn">查看检测任务</RouterLink>
            </div>
            <div v-if="selfAssetsLoading" class="page-loading">载入中...</div>
            <div v-else-if="!hasMyPatrolTasks" class="empty-state">暂无巡检任务</div>
            <ul v-else class="asset-list">
              <li v-for="task in myPatrolTasks" :key="`task-${task.id}`" class="asset-item">
                <div class="asset-main">
                  <div class="asset-title">{{ task.name || `任务 #${task.id}` }}</div>
                  <div class="asset-meta">状态：{{ patrolStatusText(task.status) }} · 机器人ID：#{{ task.robot_id }} · 创建：{{ formatDateTime(task.created_at) }}</div>
                </div>
                <div class="asset-actions">
                  <button
                    type="button"
                    class="self-square-btn"
                    :disabled="patrolOpsLoadingId === task.id || String(task.status || '').toUpperCase() === 'RUNNING'"
                    @click="setMyPatrolTaskStatus(task, 'RUNNING')">
                    运行
                  </button>
                  <button
                    type="button"
                    class="self-square-btn"
                    :disabled="patrolOpsLoadingId === task.id || String(task.status || '').toUpperCase() === 'PAUSED'"
                    @click="setMyPatrolTaskStatus(task, 'PAUSED')">
                    暂停
                  </button>
                  <button
                    type="button"
                    class="self-square-btn self-square-btn-danger"
                    :disabled="patrolOpsLoadingId === task.id"
                    @click="deleteMyPatrolTask(task)">
                    {{ patrolOpsLoadingId === task.id ? '处理中...' : '删除' }}
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </section>

      <transition name="edit-modal-fade">
        <div v-if="editingUserId !== null" class="edit-modal-mask" @click.self="cancelEdit">
          <section class="edit-modal-card card-surface" aria-label="编辑用户信息卡片">
            <h3 class="section-title section-title-sm">编辑成员信息</h3>
            <p class="user-page-subtitle" style="margin-top: 0;">
              用户 ID #{{ editingUserId }} · 当前用户名：{{ editingRowName || '-' }}
            </p>
            <form class="edit-form-grid" @submit.prevent="saveUserProfile">
              <UserProfileFields
                :form="editForm"
                password-label="新密码（留空则不修改）"
                confirm-password-label="确认新密码"
                password-placeholder="至少 6 位"
                confirm-password-placeholder="再次输入新密码"
              />
              <p class="user-page-subtitle" style="margin-top: -2px;">如不需要重置密码，请保持新密码和确认新密码为空。</p>
              <div class="edit-actions">
                <button type="submit" :disabled="savingProfileUserId !== null">
                  {{ savingProfileUserId !== null ? '保存中...' : '保存修改' }}
                </button>
                <button type="button" :disabled="savingProfileUserId !== null" @click="cancelEdit">
                  取消
                </button>
              </div>
            </form>
          </section>
        </div>
      </transition>
    </div>
  </div>
</template>

<style scoped>
.user-admin-body {
  display: grid;
  gap: var(--space-lg);
}

.user-page-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
  padding: var(--space-lg);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
}

.user-page-subtitle {
  margin: var(--space-xs) 0 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-xs);
}

.head-right {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.login-badge {
  display: inline-flex;
  align-items: center;
  border-radius: var(--radius-full);
  border: 1px solid var(--color-border-strong);
  padding: 6px 10px;
  color: var(--color-text-secondary);
  background: var(--color-bg-hover);
  font-size: var(--font-size-xs);
  font-weight: 600;
}

.error-banner {
  border-radius: var(--radius-md);
  border: 1px solid var(--color-danger-soft);
  background: var(--color-danger-soft);
  color: var(--color-danger);
  padding: var(--space-sm) var(--space-md);
  font-size: var(--font-size-sm);
}

.user-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
}

.summary-item {
  border-radius: var(--radius-md);
  padding: var(--space-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
}
.summary-label { font-size: var(--font-size-xs); color: var(--color-text-muted); }
.summary-value { margin-top: var(--space-xs); font-size: var(--font-size-xl); font-weight: 800; color: var(--color-text-primary); }

.user-main-grid {
  display: grid;
  grid-template-columns: minmax(280px, 0.9fr) minmax(0, 1.5fr);
  gap: var(--space-md);
  align-items: start;
}

.user-self-layout {
  display: grid;
  grid-template-columns: minmax(300px, 1.05fr) minmax(260px, 0.95fr);
  gap: var(--space-md);
  align-items: start;
}

.card-surface {
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-surface);
  padding: var(--space-md);
  box-shadow: var(--shadow-sm);
}

.self-profile-card,
.self-assets-card { display: grid; gap: var(--space-sm); }

.profile-card-head {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
}
.profile-avatar {
  width: 44px; height: 44px; border-radius: var(--radius-full);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: var(--font-size-lg); font-weight: 800;
  color: var(--color-text-inverse);
  background: var(--color-accent);
}
.profile-head-texts { display: grid; gap: 4px; min-width: 0; }
.profile-username { font-size: var(--font-size-base); font-weight: 700; color: var(--color-text-primary); }
.profile-meta { display: flex; flex-wrap: wrap; gap: var(--space-xs); }
.profile-tag {
  display: inline-flex; align-items: center; padding: 2px 8px;
  border-radius: var(--radius-full); font-size: var(--font-size-xs);
  color: var(--color-text-secondary); background: var(--color-bg-hover);
}

.self-profile-form { display: grid; gap: var(--space-sm); }

.assets-head {
  display: flex; justify-content: space-between; align-items: center; gap: var(--space-sm);
}

.asset-section {
  border: 1px solid var(--color-border); border-radius: var(--radius-md);
  background: var(--color-bg-elevated); padding: var(--space-sm); display: grid; gap: var(--space-sm);
}
.asset-section-head { display: flex; justify-content: space-between; align-items: center; gap: var(--space-sm); }
.asset-section-head h4 { margin: 0; font-size: var(--font-size-base); color: var(--color-text-primary); }
.asset-list { list-style: none; display: grid; gap: var(--space-sm); }
.asset-item {
  border-radius: var(--radius-md); border: 1px solid var(--color-border);
  background: var(--color-bg-surface); padding: var(--space-sm); display: grid; gap: var(--space-sm);
}
.asset-main { display: grid; gap: 4px; }
.asset-title { font-size: var(--font-size-sm); font-weight: 700; color: var(--color-text-primary); }
.asset-meta { font-size: var(--font-size-xs); color: var(--color-text-muted); line-height: 1.45; }
.asset-actions { display: flex; flex-wrap: wrap; gap: var(--space-sm); }

.self-square-btn {
  display: inline-flex; align-items: center; justify-content: center;
  min-height: 36px; padding: 0 var(--space-lg); border-radius: var(--radius-md);
  background: var(--color-accent); color: var(--color-text-inverse);
  font-size: var(--font-size-sm); font-weight: 600; text-decoration: none;
  transition: all var(--transition-fast);
}
.self-square-btn:hover { background: var(--color-accent-strong); box-shadow: var(--shadow-glow); transform: translateY(-1px); }
.self-square-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; box-shadow: none; }
.self-square-btn-danger { background: transparent; color: var(--color-danger); border: 1px solid var(--color-danger-soft); }
.self-square-btn-danger:hover { background: var(--color-danger-soft); }

.self-overview-grid { display: grid; gap: var(--space-sm); grid-template-columns: repeat(2, 1fr); }
.overview-item {
  display: grid; gap: 3px; padding: var(--space-sm); border-radius: var(--radius-sm);
  border: 1px solid var(--color-border); background: var(--color-bg-elevated);
}
.overview-label { font-size: var(--font-size-xs); color: var(--color-text-muted); }
.overview-value { font-size: var(--font-size-base); font-weight: 700; color: var(--color-text-primary); }

.user-create, .user-list { display: grid; gap: var(--space-sm); }
.create-grid { display: grid; grid-template-columns: 1fr; gap: var(--space-sm); }
.create-actions { display: flex; align-items: end; }
.edit-form-grid { display: grid; gap: var(--space-sm); }
.edit-actions { display: flex; justify-content: flex-end; gap: var(--space-sm); }

.user-list-shell {
  display: grid; gap: var(--space-sm);
  border: 1px solid var(--color-border); border-radius: var(--radius-md);
  background: var(--color-bg-elevated); padding: var(--space-sm);
}
.user-list-head {
  display: grid;
  grid-template-columns: minmax(180px, 1.1fr) minmax(170px, 1fr) minmax(120px, 0.7fr) minmax(220px, 1fr);
  gap: var(--space-sm); padding: var(--space-sm) var(--space-md);
  color: var(--color-text-muted); font-size: var(--font-size-xs); font-weight: 600;
  border-radius: var(--radius-sm); background: var(--color-bg-hover);
}
.col-right { text-align: right; }
.user-list-rows { list-style: none; display: grid; gap: var(--space-sm); }
.user-list-row {
  display: grid;
  grid-template-columns: minmax(180px, 1.1fr) minmax(170px, 1fr) minmax(120px, 0.7fr) minmax(220px, 1fr);
  gap: var(--space-sm); align-items: center; border-radius: var(--radius-md);
  border: 1px solid var(--color-border); background: var(--color-bg-surface);
  padding: var(--space-md); transition: border-color var(--transition-fast);
}
.user-list-row:hover { border-color: var(--color-border-strong); }
.user-col { min-width: 0; }

.edit-modal-mask {
  position: fixed; inset: 0; z-index: 40;
  display: grid; place-items: center; padding: var(--space-lg);
  background: rgba(0, 0, 0, 0.55); backdrop-filter: blur(4px);
}
.edit-modal-card {
  width: min(560px, 100%); display: grid; gap: var(--space-sm);
  border-radius: var(--radius-lg); border: 1px solid var(--color-border-strong);
  box-shadow: var(--shadow-lg);
}

.edit-modal-fade-enter-active, .edit-modal-fade-leave-active { transition: opacity 180ms ease; }
.edit-modal-fade-enter-from, .edit-modal-fade-leave-to { opacity: 0; }

.user-item-name {
  color: var(--color-text-primary); font-weight: 700;
  display: flex; align-items: center; gap: var(--space-xs);
}
.user-item-id { font-size: var(--font-size-xs); color: var(--color-text-muted); }
.user-mainline { display: flex; align-items: center; gap: var(--space-sm); }
.user-texts { display: grid; min-width: 0; gap: 4px; }

.user-avatar {
  width: 34px; height: 34px; border-radius: var(--radius-full);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: var(--font-size-sm); font-weight: 700;
  color: var(--color-text-inverse); background: var(--color-accent);
}

.current-tag {
  margin-left: var(--space-xs); font-size: 10px;
  color: var(--color-accent); background: var(--color-accent-soft);
  border-radius: var(--radius-full); padding: 2px 8px;
}

.role-admin, .role-user {
  display: inline-flex; align-items: center; border-radius: var(--radius-full);
  padding: 4px 10px; font-size: var(--font-size-xs); font-weight: 600;
}
.organization-tag {
  display: inline-flex; align-items: center; border-radius: var(--radius-full);
  padding: 4px 10px; font-size: var(--font-size-xs); font-weight: 600;
  color: var(--color-text-secondary); background: var(--color-bg-hover);
}
.role-admin { color: var(--color-success); background: var(--color-success-soft); }
.role-user { color: var(--color-warning); background: var(--color-warning-soft); }

.user-col-actions .btn-detail,
.user-col-actions .btn-delete { min-height: 34px; padding: 0 var(--space-md); }

.user-row-pop-enter-active { animation: user-row-pop 320ms ease; }
@keyframes user-row-pop {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 980px) {
  .user-summary, .user-main-grid, .user-self-layout { grid-template-columns: 1fr; }
  .user-list-head { display: none; }
  .user-list-row { grid-template-columns: 1fr; }
  .user-col-actions { justify-content: flex-start; }
  .self-overview-grid { grid-template-columns: 1fr; }
}
</style>

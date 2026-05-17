<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { getJson, postJson } from '../lib/api'
import { pushFlash, sessionState } from '../stores/session'

const loading = ref(false)
const submitting = ref(false)
const updatingUserId = ref(null)
const deletingUserId = ref(null)
const editingUserId = ref(null)
const savingProfileUserId = ref(null)
const editingRowName = ref('')
const rows = ref([])
const loadError = ref('')
const canManage = ref(false)
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

const authUser = computed(() => sessionState.user)
const hasRows = computed(() => rows.value.length > 0)
const currentRoleText = computed(() => (authUser.value?.role === 'admin' ? '管理员' : '普通用户'))

function resetSummary() {
  summary.value = { total: 0, admin_count: 0, user_count: 0 }
}

function roleText(role) {
  return role === 'admin' ? '管理员' : '普通用户'
}

function resetCreateForm() {
  createForm.username = ''
  createForm.organization = ''
  createForm.password = ''
  createForm.confirm_password = ''
  createForm.security_code = ''
  createForm.role = 'user'
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

async function createUser() {
  if (!canManage.value) {
    pushFlash('当前账号只有查看权限，无法创建用户', 'warning')
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
          <h3 class="section-title">账号与权限管理</h3>
          <p class="user-page-subtitle">数据来源：后端数据库 user 表（接口：/api/web/admin/users）</p>
        </div>
        <div class="head-right">
          <span class="login-badge">当前身份：{{ currentRoleText }}</span>
          <button type="button" class="btn-detail" @click="loadUsers">刷新数据</button>
        </div>
      </section>

      <div v-if="loadError" class="error-banner">
        <strong>加载失败：</strong>{{ loadError }}
      </div>

      <section class="user-summary" aria-label="用户摘要">
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

      <section class="user-main-grid" aria-label="用户管理主体">
        <div v-if="canManage" class="user-create card-surface" aria-label="创建用户">
          <h3 class="section-title section-title-sm">创建新用户</h3>
          <form class="create-grid" @submit.prevent="createUser">
            <label class="field-block">
              <span>用户名</span>
              <input v-model.trim="createForm.username" maxlength="50" minlength="3" required>
            </label>
            <label class="field-block">
              <span>所属单位</span>
              <input v-model.trim="createForm.organization" maxlength="120" required placeholder="例如：XX 环卫中心">
            </label>
            <label class="field-block">
              <span>密码</span>
              <input v-model="createForm.password" type="password" minlength="6" required>
            </label>
            <label class="field-block">
              <span>确认密码</span>
              <input v-model="createForm.confirm_password" type="password" minlength="6" required>
            </label>
            <label class="field-block">
              <span>安全码</span>
              <input v-model.trim="createForm.security_code" maxlength="32" required>
            </label>
            <label class="field-block">
              <span>角色</span>
              <select v-model="createForm.role" required>
                <option value="user">普通用户</option>
                <option value="admin">管理员</option>
              </select>
            </label>
            <div class="create-actions">
              <button type="submit" :disabled="submitting">
                {{ submitting ? '创建中...' : '创建用户' }}
              </button>
            </div>
          </form>
        </div>

        <div v-else class="user-create card-surface" aria-label="只读提示">
          <h3 class="section-title section-title-sm">当前为只读模式</h3>
          <p class="user-page-subtitle" style="margin-top: 0;">
            你正在查看后端 user 表数据，但当前账号不是管理员，因此不能新增、编辑、改角色或删除用户。
          </p>
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
                  <button
                    v-if="canManage"
                    type="button"
                    class="btn-detail"
                    :disabled="row.is_current_user || editingUserId !== null"
                    @click="startEdit(row)"
                  >
                    编辑信息
                  </button>
                  <button
                    v-if="canManage"
                    type="button"
                    class="btn-detail"
                    :disabled="updatingUserId === row.id || editingUserId !== null || (row.is_current_user && row.role === 'admin')"
                    @click="toggleRole(row)"
                  >
                    {{ updatingUserId === row.id ? '更新中...' : (row.role === 'admin' ? '降为普通用户' : '设为管理员') }}
                  </button>
                  <button
                    v-if="canManage"
                    type="button"
                    class="btn-delete"
                    :disabled="deletingUserId === row.id || editingUserId !== null || row.is_current_user"
                    @click="removeUser(row)"
                  >
                    {{ deletingUserId === row.id ? '删除中...' : '删除' }}
                  </button>
                </div>
              </li>
            </transition-group>
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
              <label class="field-block">
                <span>用户名</span>
                <input v-model.trim="editForm.username" maxlength="50" minlength="3" required>
              </label>
              <label class="field-block">
                <span>所属单位</span>
                <input v-model.trim="editForm.organization" maxlength="120" required placeholder="例如：XX 环卫中心">
              </label>
              <label class="field-block">
                <span>新密码（留空则不修改）</span>
                <input v-model="editForm.password" type="password" minlength="6" placeholder="至少 6 位">
              </label>
              <label class="field-block">
                <span>确认新密码</span>
                <input v-model="editForm.confirm_password" type="password" minlength="6" placeholder="再次输入新密码">
              </label>
              <p class="user-page-subtitle" style="margin-top: -2px;">如不需要重置密码，请保持新密码和确认新密码为空。</p>
              <div class="edit-actions">
                <button type="submit" :disabled="savingProfileUserId !== null">
                  {{ savingProfileUserId !== null ? '保存中...' : '保存修改' }}
                </button>
                <button type="button" class="btn-detail" :disabled="savingProfileUserId !== null" @click="cancelEdit">
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
  gap: 14px;
}

.user-page-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 14px;
  border-radius: 12px;
  border: 1px solid rgba(20, 110, 89, 0.22);
  background: linear-gradient(145deg, rgba(245, 252, 249, 0.88), rgba(229, 245, 238, 0.78));
}

.user-page-subtitle {
  margin: 6px 0 0;
  color: #4d766d;
  font-size: 12px;
}

.head-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.login-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  border: 1px solid rgba(22, 116, 93, 0.34);
  padding: 6px 10px;
  color: #145647;
  background: rgba(255, 255, 255, 0.76);
  font-size: 12px;
  font-weight: 700;
}

.error-banner {
  border-radius: 10px;
  border: 1px solid rgba(185, 57, 70, 0.38);
  background: rgba(251, 235, 237, 0.92);
  color: #8a2e39;
  padding: 10px 12px;
  font-size: 13px;
}

.user-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.summary-item {
  border-radius: 12px;
  padding: 14px;
  border: 1px solid rgba(27, 110, 88, 0.16);
  background: linear-gradient(160deg, rgba(245, 252, 249, 0.9), rgba(237, 249, 244, 0.72));
  box-shadow: 0 8px 18px rgba(31, 109, 90, 0.08);
}

.summary-label {
  font-size: 12px;
  color: #4d766d;
}

.summary-value {
  margin-top: 6px;
  font-size: 22px;
  font-weight: 800;
  color: #17463d;
}

.user-main-grid {
  display: grid;
  grid-template-columns: minmax(280px, 0.9fr) minmax(0, 1.5fr);
  gap: 12px;
  align-items: start;
}

.user-create,
.user-list {
  display: grid;
  gap: 10px;
}

.card-surface {
  border-radius: 12px;
  border: 1px solid rgba(27, 110, 88, 0.16);
  background: rgba(255, 255, 255, 0.92);
  padding: 12px;
  box-shadow: 0 10px 24px rgba(35, 105, 88, 0.08);
}

.create-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.create-actions {
  display: flex;
  align-items: end;
}

.edit-form-grid {
  display: grid;
  gap: 10px;
}

.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.user-list-shell {
  display: grid;
  gap: 8px;
  border: 1px solid rgba(26, 113, 91, 0.14);
  border-radius: 12px;
  background: rgba(247, 253, 250, 0.66);
  padding: 8px;
}

.user-list-head {
  display: grid;
  grid-template-columns: minmax(180px, 1.1fr) minmax(170px, 1fr) minmax(120px, 0.7fr) minmax(220px, 1fr);
  gap: 10px;
  padding: 8px 12px;
  color: #5c7b72;
  font-size: 12px;
  font-weight: 700;
  border-radius: 10px;
  background: linear-gradient(160deg, rgba(232, 247, 240, 0.82), rgba(243, 252, 248, 0.72));
}

.col-right {
  text-align: right;
}

.user-list-rows {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 8px;
}

.user-list-row {
  display: grid;
  grid-template-columns: minmax(180px, 1.1fr) minmax(170px, 1fr) minmax(120px, 0.7fr) minmax(220px, 1fr);
  gap: 10px;
  align-items: center;
  border-radius: 10px;
  border: 1px solid rgba(21, 116, 92, 0.2);
  background: linear-gradient(155deg, rgba(255, 255, 255, 0.96), rgba(244, 252, 248, 0.9));
  padding: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.user-list-row:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px rgba(17, 113, 89, 0.16);
}

.user-col {
  min-width: 0;
}

.edit-modal-mask {
  position: fixed;
  inset: 0;
  z-index: 40;
  display: grid;
  place-items: center;
  padding: 18px;
  background: rgba(6, 31, 25, 0.35);
  backdrop-filter: blur(2px);
}

.edit-modal-card {
  width: min(560px, 100%);
  display: grid;
  gap: 10px;
  border-radius: 14px;
  border: 1px solid rgba(20, 109, 88, 0.26);
  box-shadow: 0 18px 44px rgba(10, 60, 49, 0.24);
}

.edit-modal-fade-enter-active,
.edit-modal-fade-leave-active {
  transition: opacity 180ms ease;
}

.edit-modal-fade-enter-from,
.edit-modal-fade-leave-to {
  opacity: 0;
}

.user-item-name {
  color: #184d42;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 6px;
}

.user-item-id {
  font-size: 12px;
  color: #5e7c74;
}

.user-mainline {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-texts {
  display: grid;
  min-width: 0;
  gap: 4px;
}

.edit-inline-block {
  display: grid;
  gap: 4px;
}

.edit-input {
  width: 100%;
  min-height: 32px;
  border-radius: 8px;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 800;
  color: #125745;
  background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.9), rgba(182, 230, 211, 0.92));
  border: 1px solid rgba(18, 110, 89, 0.25);
}

.current-tag {
  margin-left: 6px;
  font-size: 10px;
  color: #0e5f4d;
  background: rgba(16, 127, 100, 0.14);
  border-radius: 999px;
  padding: 2px 8px;
}

.role-admin,
.role-user {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 700;
}

.organization-tag {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 700;
  color: #2a6256;
  background: rgba(110, 177, 158, 0.18);
}

.role-admin {
  color: #0f5a46;
  background: rgba(23, 144, 111, 0.18);
}

.role-user {
  color: #6a522f;
  background: rgba(214, 152, 71, 0.18);
}

.user-col-actions .btn-detail,
.user-col-actions .btn-delete {
  min-height: 34px;
  padding: 0 12px;
}

.user-row-pop-enter-active {
  animation: user-row-pop 320ms ease;
}

@keyframes user-row-pop {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 980px) {
  .user-summary,
  .user-main-grid {
    grid-template-columns: 1fr;
  }

  .user-list-head {
    display: none;
  }

  .user-list-row {
    grid-template-columns: 1fr;
  }

  .user-col-actions {
    justify-content: flex-start;
  }
}
</style>

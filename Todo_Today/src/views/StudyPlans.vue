<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useStorage } from '@/composables/useStorage'

interface StudyPlan {
  id: number
  subject: string
  description: string
  duration: number
  completed: boolean
}

let nextId = Date.now()

const plans = useStorage<StudyPlan[]>('todo-study-plans', [])
const newSubject = ref('')
const newDescription = ref('')
const newDuration = ref(30)
const editingId = ref<number | null>(null)
const editSubject = ref('')
const editDescription = ref('')
const editDuration = ref(30)
const editInputRef = ref<HTMLInputElement | null>(null)
const filterView = ref<'all' | 'active' | 'completed'>('all')

const filteredPlans = computed(() => {
  if (filterView.value === 'active') return plans.value.filter((p) => !p.completed)
  if (filterView.value === 'completed') return plans.value.filter((p) => p.completed)
  return plans.value
})

const completedCount = computed(() => plans.value.filter((p) => p.completed).length)
const activeCount = computed(() => plans.value.filter((p) => !p.completed).length)
const totalMinutes = computed(() => plans.value.reduce((s, p) => s + p.duration, 0))
const progressPercent = computed(() => {
  if (plans.value.length === 0) return 0
  return (completedCount.value / plans.value.length) * 100
})

const addPlan = () => {
  const subject = newSubject.value.trim()
  if (!subject) return

  plans.value.unshift({
    id: nextId++,
    subject,
    description: newDescription.value.trim(),
    duration: newDuration.value,
    completed: false,
  })
  newSubject.value = ''
  newDescription.value = ''
  newDuration.value = 30
}

const togglePlan = (id: number) => {
  const plan = plans.value.find((p) => p.id === id)
  if (plan) plan.completed = !plan.completed
}

const startEdit = (plan: StudyPlan) => {
  editingId.value = plan.id
  editSubject.value = plan.subject
  editDescription.value = plan.description
  editDuration.value = plan.duration
  nextTick(() => editInputRef.value?.focus())
}

const saveEdit = () => {
  if (editingId.value === null) return
  const plan = plans.value.find((p) => p.id === editingId.value)
  if (!plan) return
  const subject = editSubject.value.trim()
  if (!subject) {
    removePlan(editingId.value)
    return
  }
  plan.subject = subject
  plan.description = editDescription.value.trim()
  plan.duration = editDuration.value
  editingId.value = null
}

const cancelEdit = () => {
  editingId.value = null
}

const removePlan = (id: number) => {
  plans.value = plans.value.filter((p) => p.id !== id)
}

const clearCompleted = () => {
  plans.value = plans.value.filter((p) => !p.completed)
}
</script>

<template>
  <div class="container page">
    <header class="page-header">
      <div>
        <h1>Study Plans</h1>
        <p class="page-subtitle">Optimize your learning trajectory</p>
      </div>
      <div class="page-stats">
        <div class="stat">
          <span class="stat-value">{{ plans.length }}</span>
          <span class="stat-label">Plans</span>
        </div>
        <div class="stat accent">
          <span class="stat-value">{{ completedCount }}</span>
          <span class="stat-label">Done</span>
        </div>
        <div class="stat">
          <span class="stat-value">{{ totalMinutes }}</span>
          <span class="stat-label">Min</span>
        </div>
      </div>
    </header>

    <div class="add-plan">
      <div class="input-wrapper">
        <span class="input-icon">📘</span>
        <input
          v-model="newSubject"
          type="text"
          placeholder="Subject name..."
          @keyup.enter="addPlan"
        />
      </div>
      <div class="input-wrapper">
        <span class="input-icon">📝</span>
        <input
          v-model="newDescription"
          type="text"
          placeholder="Description (optional)..."
          @keyup.enter="addPlan"
        />
      </div>
      <div class="duration-input">
        <input v-model.number="newDuration" type="number" min="5" max="480" step="5" />
        <span class="duration-unit">min</span>
      </div>
      <button class="btn-glow" @click="addPlan">Add Plan</button>
    </div>

    <div v-if="plans.length > 0" class="toolbar">
      <div class="filter-tabs">
        <button :class="{ active: filterView === 'all' }" @click="filterView = 'all'">All ({{ plans.length }})</button>
        <button :class="{ active: filterView === 'active' }" @click="filterView = 'active'">Active ({{ activeCount }})</button>
        <button :class="{ active: filterView === 'completed' }" @click="filterView = 'completed'">Completed ({{ completedCount }})</button>
      </div>
      <button v-if="completedCount > 0" class="btn-clear" @click="clearCompleted">Clear completed</button>
    </div>

    <div v-if="plans.length > 0" class="progress-bar">
      <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
    </div>

    <div v-if="plans.length === 0" class="empty-state">
      <div class="empty-icon">📚</div>
      <h3>No study plans yet</h3>
      <p>Create your first study plan above</p>
    </div>

    <div v-else-if="filteredPlans.length === 0" class="empty-state">
      <div class="empty-icon">🔍</div>
      <h3>No {{ filterView }} plans</h3>
      <p>Switch to a different filter view</p>
    </div>

    <ul v-else class="plan-list">
      <li
        v-for="plan in filteredPlans"
        :key="plan.id"
        :class="{ completed: plan.completed, editing: editingId === plan.id }"
        class="plan-item"
      >
        <button
          class="check-btn"
          :class="{ checked: plan.completed }"
          @click="togglePlan(plan.id)"
        >
          <span v-if="plan.completed">✓</span>
        </button>

        <div v-if="editingId !== plan.id" class="plan-content" @dblclick="startEdit(plan)">
          <div class="plan-title-row">
            <span class="plan-subject">{{ plan.subject }}</span>
            <div class="plan-duration-badge">{{ plan.duration }} min</div>
          </div>
          <span v-if="plan.description" class="plan-desc">{{ plan.description }}</span>
        </div>

        <div v-else class="plan-edit">
          <input
            ref="editInputRef"
            v-model="editSubject"
            type="text"
            class="edit-input"
            @keyup.enter="saveEdit"
            @keyup.escape="cancelEdit"
            @blur="saveEdit"
          />
          <input v-model="editDescription" type="text" class="edit-input-desc" placeholder="Description..." />
          <div class="duration-input-edit">
            <input v-model.number="editDuration" type="number" min="5" max="480" step="5" />
            <span class="duration-unit-small">min</span>
          </div>
          <div class="edit-actions">
            <button class="btn-save" @click="saveEdit">✓</button>
            <button class="btn-cancel" @click="cancelEdit">✗</button>
          </div>
        </div>

        <button class="btn-delete" @click="removePlan(plan.id)">
          <span>×</span>
        </button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.page { padding: 40px 0; }

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0;
}

.page-stats {
  display: flex;
  gap: 12px;
}

.stat {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 12px 20px;
  text-align: center;
  min-width: 80px;
}

.stat.accent {
  border-color: rgba(0, 212, 255, 0.3);
}

.stat-value {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: var(--text-color);
}

.stat-label {
  display: block;
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 2px;
}

.add-plan {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}

.input-wrapper {
  position: relative;
  flex: 1;
  min-width: 180px;
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 15px;
  pointer-events: none;
}

.input-wrapper input {
  width: 100%;
  padding: 14px 14px 14px 42px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  font-size: 14px;
  color: var(--text-color);
  transition: all 0.25s ease;
}

.input-wrapper input::placeholder {
  color: rgba(148, 163, 184, 0.4);
}

.input-wrapper input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
}

.duration-input {
  display: flex;
  align-items: center;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
}

.duration-input input {
  width: 70px;
  padding: 14px 8px;
  background: transparent;
  border: none;
  font-size: 14px;
  color: var(--text-color);
  text-align: center;
}

.duration-input input:focus {
  outline: none;
}

.duration-unit {
  padding: 0 12px;
  font-size: 13px;
  color: var(--text-muted);
  border-left: 1px solid var(--border-color);
}

.btn-glow {
  padding: 14px 24px;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 0 20px var(--primary-glow);
  white-space: nowrap;
}

.btn-glow:hover {
  box-shadow: 0 0 36px var(--primary-glow);
  transform: translateY(-1px);
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.filter-tabs {
  display: flex;
  gap: 4px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 4px;
}

.filter-tabs button {
  padding: 6px 14px;
  background: transparent;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
}

.filter-tabs button:hover { color: var(--text-color); }
.filter-tabs button.active {
  background: rgba(0, 212, 255, 0.12);
  color: var(--primary-color);
}

.btn-clear {
  padding: 6px 14px;
  background: transparent;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  font-size: 13px;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-clear:hover { border-color: #f87171; color: #f87171; }

.progress-bar {
  margin-bottom: 20px;
  height: 4px;
  background: rgba(15, 23, 42, 0.8);
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
  border-radius: 2px;
  transition: width 0.4s ease;
  box-shadow: 0 0 12px var(--primary-glow);
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 18px;
  color: var(--text-muted);
  margin: 0 0 8px 0;
  font-weight: 500;
}

.empty-state p {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0;
  opacity: 0.6;
}

.plan-list { list-style: none; margin: 0; padding: 0; }

.plan-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  margin-bottom: 8px;
  transition: all 0.25s ease;
}

.plan-item:hover {
  background: var(--bg-card-hover);
  border-color: rgba(0, 212, 255, 0.2);
}

.plan-item.completed { opacity: 0.6; }
.plan-item.completed .plan-subject {
  text-decoration: line-through;
  color: var(--text-muted);
}

.plan-item.editing {
  border-color: var(--primary-color);
  box-shadow: 0 0 16px rgba(0, 212, 255, 0.1);
}

.check-btn {
  width: 24px;
  height: 24px;
  border: 2px solid var(--border-color);
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  flex-shrink: 0;
  color: #0a0a1a;
  font-size: 14px;
  font-weight: 700;
}

.check-btn:hover { border-color: var(--primary-color); }
.check-btn.checked {
  background: var(--primary-color);
  border-color: var(--primary-color);
  box-shadow: 0 0 12px var(--primary-glow);
}

.plan-content { flex: 1; display: flex; flex-direction: column; gap: 4px; cursor: default; }

.plan-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.plan-subject { font-size: 15px; color: var(--text-color); font-weight: 600; }
.plan-desc { font-size: 12px; color: var(--text-muted); }

.plan-duration-badge {
  font-size: 12px;
  color: var(--primary-color);
  font-weight: 500;
  background: rgba(0, 212, 255, 0.08);
  padding: 2px 10px;
  border-radius: 20px;
}

.plan-edit { flex: 1; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

.edit-input {
  flex: 1;
  min-width: 120px;
  padding: 8px 12px;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--primary-color);
  border-radius: 8px;
  font-size: 15px;
  color: var(--text-color);
  outline: none;
}

.edit-input-desc {
  width: 140px;
  padding: 8px 12px;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  font-size: 13px;
  color: var(--text-color);
  outline: none;
}

.duration-input-edit {
  display: flex;
  align-items: center;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  overflow: hidden;
}

.duration-input-edit input {
  width: 60px;
  padding: 8px;
  background: transparent;
  border: none;
  color: var(--text-color);
  text-align: center;
  outline: none;
}

.duration-unit-small {
  padding: 0 8px;
  font-size: 11px;
  color: var(--text-muted);
  border-left: 1px solid var(--border-color);
}

.edit-actions { display: flex; gap: 6px; }

.btn-save, .btn-cancel {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-save { background: var(--primary-color); color: #0a0a1a; }
.btn-save:hover { box-shadow: 0 0 12px var(--primary-glow); }
.btn-cancel { background: transparent; color: var(--text-muted); border: 1px solid var(--border-color); }
.btn-cancel:hover { color: #f87171; border-color: #f87171; }

.btn-delete {
  width: 32px;
  height: 32px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  flex-shrink: 0;
}

.btn-delete:hover {
  color: #f87171;
  background: rgba(248, 113, 113, 0.1);
  border-color: rgba(248, 113, 113, 0.3);
}
</style>
<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useStorage } from '@/composables/useStorage'

interface Task {
  id: number
  title: string
  completed: boolean
  createdAt: string
  dueDate: string
}

let nextId = Date.now()

const tasks = useStorage<Task[]>('todo-tasks', [])
const newTaskTitle = ref('')
const newTaskDueDate = ref('')
const editingId = ref<number | null>(null)
const editTitle = ref('')
const editDueDate = ref('')
const editInputRef = ref<HTMLInputElement | null>(null)
const filterView = ref<'all' | 'active' | 'completed'>('all')

const filteredTasks = computed(() => {
  if (filterView.value === 'active') return tasks.value.filter((t) => !t.completed)
  if (filterView.value === 'completed') return tasks.value.filter((t) => t.completed)
  return tasks.value
})

const completedCount = computed(() => tasks.value.filter((t) => t.completed).length)
const activeCount = computed(() => tasks.value.filter((t) => !t.completed).length)
const progressPercent = computed(() => {
  if (tasks.value.length === 0) return 0
  return (completedCount.value / tasks.value.length) * 100
})

const addTask = () => {
  const title = newTaskTitle.value.trim()
  if (!title) return

  tasks.value.unshift({
    id: nextId++,
    title,
    completed: false,
    createdAt: new Date().toISOString(),
    dueDate: newTaskDueDate.value ? new Date(newTaskDueDate.value).toISOString() : '',
  })
  newTaskTitle.value = ''
  newTaskDueDate.value = ''
}

const toggleTask = (id: number) => {
  const task = tasks.value.find((t) => t.id === id)
  if (task) {
    task.completed = !task.completed
  }
}

const startEdit = (task: Task) => {
  editingId.value = task.id
  editTitle.value = task.title
  editDueDate.value = task.dueDate ? task.dueDate.slice(0, 10) : ''
  nextTick(() => editInputRef.value?.focus())
}

const saveEdit = () => {
  if (editingId.value === null) return
  const task = tasks.value.find((t) => t.id === editingId.value)
  if (!task) return
  const title = editTitle.value.trim()
  if (!title) {
    removeTask(editingId.value)
    return
  }
  task.title = title
  task.dueDate = editDueDate.value ? new Date(editDueDate.value).toISOString() : ''
  editingId.value = null
}

const cancelEdit = () => {
  editingId.value = null
}

const removeTask = (id: number) => {
  tasks.value = tasks.value.filter((t) => t.id !== id)
}

const clearCompleted = () => {
  tasks.value = tasks.value.filter((t) => !t.completed)
}

const formatDate = (iso: string) => {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const formatTime = (iso: string) => {
  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const isOverdue = (task: Task) => {
  if (!task.dueDate || task.completed) return false
  return new Date(task.dueDate) < new Date()
}
</script>

<template>
  <div class="container page">
    <header class="page-header">
      <div>
        <h1>Tasks</h1>
        <p class="page-subtitle">Your mission objectives</p>
      </div>
      <div class="page-stats">
        <div class="stat">
          <span class="stat-value">{{ tasks.length }}</span>
          <span class="stat-label">Total</span>
        </div>
        <div class="stat accent">
          <span class="stat-value">{{ completedCount }}</span>
          <span class="stat-label">Done</span>
        </div>
      </div>
    </header>

    <div class="add-task">
      <div class="input-wrapper">
        <span class="input-icon">+</span>
        <input
          v-model="newTaskTitle"
          type="text"
          placeholder="Add a new task..."
          @keyup.enter="addTask"
        />
      </div>
      <div class="due-date-input">
        <input v-model="newTaskDueDate" type="date" title="Due date" />
      </div>
      <button class="btn-glow" @click="addTask">Add</button>
    </div>

    <div v-if="tasks.length > 0" class="toolbar">
      <div class="filter-tabs">
        <button
          :class="{ active: filterView === 'all' }"
          @click="filterView = 'all'"
        >All ({{ tasks.length }})</button>
        <button
          :class="{ active: filterView === 'active' }"
          @click="filterView = 'active'"
        >Active ({{ activeCount }})</button>
        <button
          :class="{ active: filterView === 'completed' }"
          @click="filterView = 'completed'"
        >Completed ({{ completedCount }})</button>
      </div>
      <button
        v-if="completedCount > 0"
        class="btn-clear"
        @click="clearCompleted"
      >Clear completed</button>
    </div>

    <div v-if="tasks.length !== 0 && tasks.length > 0" class="progress-bar">
      <div
        class="progress-fill"
        :style="{ width: progressPercent + '%' }"
      ></div>
    </div>

    <div v-if="tasks.length === 0" class="empty-state">
      <div class="empty-icon">📋</div>
      <h3>No tasks yet</h3>
      <p>Start by creating your first task above</p>
    </div>

    <div v-else-if="filteredTasks.length === 0" class="empty-state">
      <div class="empty-icon">🔍</div>
      <h3>No {{ filterView }} tasks</h3>
      <p>Switch to a different filter view</p>
    </div>

    <ul v-else class="task-list">
      <li
        v-for="task in filteredTasks"
        :key="task.id"
        :class="{
          completed: task.completed,
          editing: editingId === task.id,
          overdue: isOverdue(task),
        }"
        class="task-item"
      >
        <button
          class="check-btn"
          :class="{ checked: task.completed }"
          @click="toggleTask(task.id)"
        >
          <span v-if="task.completed">✓</span>
        </button>

        <div v-if="editingId !== task.id" class="task-content" @dblclick="startEdit(task)">
          <div class="task-title-row">
            <span class="task-title">{{ task.title }}</span>
            <span v-if="isOverdue(task)" class="overdue-tag">Overdue</span>
          </div>
          <div class="task-meta">
            <span class="task-time">{{ formatTime(task.createdAt) }}</span>
            <span v-if="task.dueDate" class="task-due">Due {{ formatDate(task.dueDate) }}</span>
          </div>
        </div>

        <div v-else class="task-edit">
          <input
            ref="editInputRef"
            v-model="editTitle"
            type="text"
            class="edit-input"
            @keyup.enter="saveEdit"
            @keyup.escape="cancelEdit"
            @blur="saveEdit"
          />
          <input v-model="editDueDate" type="date" class="edit-date" />
          <div class="edit-actions">
            <button class="btn-save" @click="saveEdit">✓</button>
            <button class="btn-cancel" @click="cancelEdit">✗</button>
          </div>
        </div>

        <button class="btn-delete" @click="removeTask(task.id)">
          <span>×</span>
        </button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.page {
  padding: 40px 0;
}

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

.add-task {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.input-wrapper {
  position: relative;
  flex: 1;
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary-color);
  font-size: 18px;
  font-weight: 600;
  pointer-events: none;
}

.input-wrapper input {
  width: 100%;
  padding: 14px 14px 14px 40px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  font-size: 15px;
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

.due-date-input input {
  padding: 14px 12px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  font-size: 14px;
  color: var(--text-color);
  outline: none;
  transition: all 0.25s ease;
  width: 150px;
  color-scheme: dark;
}

.due-date-input input:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
}

.btn-glow {
  padding: 14px 28px;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 0 20px var(--primary-glow);
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

.filter-tabs button:hover {
  color: var(--text-color);
}

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

.btn-clear:hover {
  border-color: #f87171;
  color: #f87171;
}

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

.task-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.task-item {
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

.task-item:hover {
  background: var(--bg-card-hover);
  border-color: rgba(0, 212, 255, 0.2);
}

.task-item.completed {
  opacity: 0.6;
}

.task-item.overdue {
  border-color: rgba(248, 113, 113, 0.3);
}

.task-item.completed .task-title {
  text-decoration: line-through;
  color: var(--text-muted);
}

.task-item.editing {
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

.check-btn:hover {
  border-color: var(--primary-color);
}

.check-btn.checked {
  background: var(--primary-color);
  border-color: var(--primary-color);
  box-shadow: 0 0 12px var(--primary-glow);
}

.task-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  cursor: default;
}

.task-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.task-title {
  font-size: 15px;
  color: var(--text-color);
}

.overdue-tag {
  font-size: 10px;
  font-weight: 600;
  color: #f87171;
  background: rgba(248, 113, 113, 0.12);
  padding: 1px 8px;
  border-radius: 10px;
}

.task-meta {
  display: flex;
  gap: 10px;
}

.task-time {
  font-size: 11px;
  color: var(--text-muted);
}

.task-due {
  font-size: 11px;
  color: var(--primary-color);
  background: rgba(0, 212, 255, 0.06);
  padding: 1px 8px;
  border-radius: 10px;
}

.task-edit {
  flex: 1;
  display: flex;
  gap: 8px;
  align-items: center;
}

.edit-input {
  flex: 1;
  padding: 8px 12px;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--primary-color);
  border-radius: 8px;
  font-size: 15px;
  color: var(--text-color);
  outline: none;
}

.edit-date {
  padding: 8px 10px;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  font-size: 13px;
  color: var(--text-color);
  outline: none;
  color-scheme: dark;
}

.edit-actions {
  display: flex;
  gap: 6px;
}

.btn-save,
.btn-cancel {
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

.btn-save {
  background: var(--primary-color);
  color: #0a0a1a;
}

.btn-save:hover {
  box-shadow: 0 0 12px var(--primary-glow);
}

.btn-cancel {
  background: transparent;
  color: var(--text-muted);
  border: 1px solid var(--border-color);
}

.btn-cancel:hover {
  color: #f87171;
  border-color: #f87171;
}

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
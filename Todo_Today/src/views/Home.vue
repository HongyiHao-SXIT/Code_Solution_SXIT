<script setup lang="ts">
import { computed } from 'vue'
import { useStorage } from '@/composables/useStorage'

interface Task { id: number; title: string; completed: boolean; createdAt: string; dueDate: string }
interface StudyPlan { id: number; subject: string; description: string; duration: number; completed: boolean }
interface ReviewItem { id: number; title: string; notes: string; rating: number; createdAt: string }

const tasks = useStorage<Task[]>('todo-tasks', [])
const plans = useStorage<StudyPlan[]>('todo-study-plans', [])
const reviews = useStorage<ReviewItem[]>('todo-reviews', [])

const taskStats = computed(() => ({
  total: tasks.value.length,
  completed: tasks.value.filter((t) => t.completed).length,
  pending: tasks.value.filter((t) => !t.completed).length,
}))

const planStats = computed(() => ({
  total: plans.value.length,
  completed: plans.value.filter((p) => p.completed).length,
  totalMinutes: plans.value.reduce((s, p) => s + p.duration, 0),
}))

const reviewStats = computed(() => {
  if (reviews.value.length === 0) return { total: 0, avg: 0 }
  return {
    total: reviews.value.length,
    avg: reviews.value.reduce((s, r) => s + r.rating, 0) / reviews.value.length,
  }
})

const today = new Date().toLocaleDateString('en-US', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
})

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 18) return 'Good afternoon'
  return 'Good evening'
})
</script>

<template>
  <div class="container page">
    <header class="page-header">
      <div>
        <h1>{{ greeting }}, Commander</h1>
        <p class="page-subtitle">{{ today }}</p>
      </div>
    </header>

    <div class="stats-grid">
      <router-link to="/tasks" class="stat-card tasks">
        <div class="stat-icon">📋</div>
        <div class="stat-data">
          <span class="stat-value">{{ taskStats.completed }}/{{ taskStats.total }}</span>
          <span class="stat-label">Tasks completed</span>
        </div>
        <div class="stat-bar">
          <div
            class="stat-bar-fill"
            :style="{ width: taskStats.total ? (taskStats.completed / taskStats.total) * 100 + '%' : '0%' }"
          ></div>
        </div>
      </router-link>

      <router-link to="/study-plans" class="stat-card plans">
        <div class="stat-icon">📚</div>
        <div class="stat-data">
          <span class="stat-value">{{ planStats.completed }}/{{ planStats.total }}</span>
          <span class="stat-label">Study plans done</span>
        </div>
        <div class="extra-info">{{ planStats.totalMinutes }} min planned</div>
      </router-link>

      <router-link to="/weekly-review" class="stat-card reviews">
        <div class="stat-icon">📊</div>
        <div class="stat-data">
          <span class="stat-value">{{ reviewStats.avg.toFixed(1) }} ★</span>
          <span class="stat-label">Avg. rating</span>
        </div>
        <div class="extra-info">{{ reviewStats.total }} reviews</div>
      </router-link>

      <router-link to="/profile" class="stat-card profile">
        <div class="stat-icon">⚙️</div>
        <div class="stat-data">
          <span class="stat-value">Profile</span>
          <span class="stat-label">Settings & preferences</span>
        </div>
      </router-link>
    </div>

    <div v-if="tasks.filter((t: Task) => !t.completed).length > 0" class="quick-tasks">
      <h2>Pending Tasks</h2>
      <div class="quick-task-list">
        <div
          v-for="task in tasks.filter((t: Task) => !t.completed).slice(0, 5)"
          :key="task.id"
          class="quick-task-item"
        >
          <span class="quick-dot"></span>
          <span>{{ task.title }}</span>
          <span v-if="task.dueDate" class="due-badge">{{ new Date(task.dueDate).toLocaleDateString() }}</span>
        </div>
      </div>
      <router-link v-if="tasks.filter((t: Task) => !t.completed).length > 5" to="/tasks" class="see-all">
        See all {{ tasks.filter((t: Task) => !t.completed).length }} pending →
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.page {
  padding: 40px 0;
}

.page-header {
  margin-bottom: 40px;
}

.page-header h1 {
  font-size: 32px;
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 4px 0;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  font-size: 15px;
  color: var(--text-muted);
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 40px;
}

.stat-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 24px;
  text-decoration: none;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.stat-card.tasks::before {
  background: radial-gradient(circle at top right, rgba(0, 212, 255, 0.06), transparent 70%);
}
.stat-card.plans::before {
  background: radial-gradient(circle at top right, rgba(124, 58, 237, 0.06), transparent 70%);
}
.stat-card.reviews::before {
  background: radial-gradient(circle at top right, rgba(244, 114, 182, 0.06), transparent 70%);
}
.stat-card.profile::before {
  background: radial-gradient(circle at top right, rgba(0, 212, 255, 0.04), transparent 70%);
}

.stat-card:hover {
  border-color: rgba(0, 212, 255, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(0, 212, 255, 0.08);
}

.stat-card:hover::before {
  opacity: 1;
}

.stat-icon {
  font-size: 28px;
  margin-bottom: 16px;
}

.stat-data {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 16px;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-color);
}

.stat-label {
  font-size: 13px;
  color: var(--text-muted);
}

.stat-bar {
  height: 4px;
  background: rgba(15, 23, 42, 0.6);
  border-radius: 2px;
  overflow: hidden;
}

.stat-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
  border-radius: 2px;
  transition: width 0.6s ease;
  box-shadow: 0 0 8px var(--primary-glow);
}

.extra-info {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 12px;
}

.quick-tasks h2 {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 16px 0;
}

.quick-task-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.quick-task-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  font-size: 14px;
  color: var(--text-color);
}

.quick-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--primary-color);
  box-shadow: 0 0 8px var(--primary-glow);
  flex-shrink: 0;
}

.due-badge {
  font-size: 11px;
  color: var(--primary-color);
  background: rgba(0, 212, 255, 0.08);
  padding: 2px 10px;
  border-radius: 20px;
  margin-left: auto;
}

.see-all {
  display: inline-block;
  font-size: 14px;
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.25s ease;
}

.see-all:hover {
  text-decoration: underline;
}
</style>
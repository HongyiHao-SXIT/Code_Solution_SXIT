<script setup lang="ts">
import { ref, computed } from 'vue'
import { useStorage } from '@/composables/useStorage'

interface ReviewItem {
  id: number
  title: string
  notes: string
  rating: number
  createdAt: string
}

let nextId = Date.now()

const reviews = useStorage<ReviewItem[]>('todo-reviews', [])
const newTitle = ref('')
const newNotes = ref('')
const newRating = ref(3)

const averageRating = computed(() => {
  if (reviews.value.length === 0) return 0
  return reviews.value.reduce((s, r) => s + r.rating, 0) / reviews.value.length
})

const addReview = () => {
  const title = newTitle.value.trim()
  if (!title) return

  reviews.value.unshift({
    id: nextId++,
    title,
    notes: newNotes.value.trim(),
    rating: newRating.value,
    createdAt: new Date().toISOString(),
  })
  newTitle.value = ''
  newNotes.value = ''
  newRating.value = 3
}

const removeReview = (id: number) => {
  reviews.value = reviews.value.filter((r) => r.id !== id)
}

const ratingLabel = (rating: number): string => {
  const labels: Record<number, string> = {
    1: 'Poor',
    2: 'Fair',
    3: 'Good',
    4: 'Great',
    5: 'Excellent',
  }
  return labels[rating] ?? 'N/A'
}
</script>

<template>
  <div class="container page">
    <header class="page-header">
      <div>
        <h1>Weekly Review</h1>
        <p class="page-subtitle">Analyze and optimize your progress</p>
      </div>
      <div class="page-stats">
        <div class="stat">
          <span class="stat-value">{{ reviews.length }}</span>
          <span class="stat-label">Items</span>
        </div>
        <div class="stat accent">
          <span class="stat-value">{{ averageRating.toFixed(1) }}</span>
          <span class="stat-label">Avg. Rating</span>
        </div>
      </div>
    </header>

    <div class="add-review">
      <div class="input-wrapper">
        <span class="input-icon">💡</span>
        <input
          v-model="newTitle"
          type="text"
          placeholder="What did you accomplish this week?"
          @keyup.enter="addReview"
        />
      </div>
      <div class="input-wrapper notes-wrapper">
        <span class="input-icon">📝</span>
        <input
          v-model="newNotes"
          type="text"
          placeholder="Notes (optional)..."
          @keyup.enter="addReview"
        />
      </div>
      <div class="rating-select">
        <select v-model.number="newRating">
          <option :value="1">★☆☆☆☆</option>
          <option :value="2">★★☆☆☆</option>
          <option :value="3">★★★☆☆</option>
          <option :value="4">★★★★☆</option>
          <option :value="5">★★★★★</option>
        </select>
      </div>
      <button class="btn-glow" @click="addReview">Add Review</button>
    </div>

    <div v-if="reviews.length === 0" class="empty-state">
      <div class="empty-icon">📊</div>
      <h3>No reviews yet</h3>
      <p>Start your weekly review journey above</p>
    </div>

    <ul v-else class="review-list">
      <li v-for="review in reviews" :key="review.id" class="review-item">
        <div class="review-header">
          <h3 class="review-title">{{ review.title }}</h3>
          <div class="review-rating">
            <span
              v-for="i in 5"
              :key="i"
              class="star"
              :class="{ filled: i <= review.rating }"
            >★</span>
          </div>
        </div>
        <p v-if="review.notes" class="review-notes">{{ review.notes }}</p>
        <div class="review-meta">
          <span class="review-date">{{ new Date(review.createdAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</span>
          <span class="review-label">{{ ratingLabel(review.rating) }}</span>
        </div>
        <button class="btn-delete" @click="removeReview(review.id)">
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
  margin-bottom: 32px;
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

.page-stats { display: flex; gap: 12px; }

.stat {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 12px 20px;
  text-align: center;
  min-width: 80px;
}

.stat.accent { border-color: rgba(0, 212, 255, 0.3); }

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

.add-review {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 32px;
}

.input-wrapper { position: relative; flex: 1; min-width: 200px; }
.notes-wrapper { min-width: 160px; }

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

.input-wrapper input::placeholder { color: rgba(148, 163, 184, 0.4); }

.input-wrapper input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
}

.rating-select select {
  padding: 14px 16px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  font-size: 14px;
  color: var(--text-color);
  cursor: pointer;
  transition: all 0.25s ease;
  outline: none;
}

.rating-select select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
}

.rating-select option { background: #0f172a; color: var(--text-color); }

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

.empty-state { text-align: center; padding: 80px 20px; }
.empty-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
.empty-state h3 { font-size: 18px; color: var(--text-muted); margin: 0 0 8px 0; font-weight: 500; }
.empty-state p { font-size: 14px; color: var(--text-muted); margin: 0; opacity: 0.6; }

.review-list { list-style: none; margin: 0; padding: 0; }

.review-item {
  padding: 20px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  margin-bottom: 8px;
  transition: all 0.25s ease;
  position: relative;
}

.review-item:hover {
  background: var(--bg-card-hover);
  border-color: rgba(0, 212, 255, 0.2);
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 8px;
}

.review-title {
  font-size: 16px;
  color: var(--text-color);
  font-weight: 600;
  margin: 0;
}

.review-rating { display: flex; gap: 2px; }

.star {
  font-size: 16px;
  color: rgba(148, 163, 184, 0.2);
  transition: color 0.25s ease;
}

.star.filled {
  color: #fbbf24;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.4);
}

.review-notes {
  font-size: 14px;
  color: var(--text-muted);
  line-height: 1.6;
  margin: 0 0 12px 0;
}

.review-meta { display: flex; align-items: center; gap: 12px; }

.review-date { font-size: 12px; color: var(--text-muted); opacity: 0.6; }

.review-label {
  font-size: 11px;
  color: var(--primary-color);
  background: rgba(0, 212, 255, 0.08);
  padding: 2px 10px;
  border-radius: 20px;
  font-weight: 500;
}

.btn-delete {
  position: absolute;
  top: 16px;
  right: 16px;
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
}

.btn-delete:hover {
  color: #f87171;
  background: rgba(248, 113, 113, 0.1);
  border-color: rgba(248, 113, 113, 0.3);
}
</style>
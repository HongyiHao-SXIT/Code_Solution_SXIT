<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { usePaperShelf } from '../composables/usePaperShelf'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
const route = useRoute()
const userStorageKey = 'academic_scholar_user'

const loading = ref(false)
const errorMessage = ref('')
const paper = ref(null)
const recommendations = ref([])
const toastMessage = ref('')
let toastTimer = null

const { favoriteIds, readingIds, setSession, toggleFavorite, toggleReading, syncPaper } = usePaperShelf()

const isFavorite = computed(() => paper.value && favoriteIds.value.has(paper.value.id))
const isReading = computed(() => paper.value && readingIds.value.has(paper.value.id))

async function fetchPaper() {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await fetch(`${apiBaseUrl}/api/papers/${route.params.id}`)
    if (!response.ok) {
      throw new Error('Paper not found or unavailable.')
    }

    paper.value = await response.json()
    syncPaper(paper.value)

    const recResponse = await fetch(`${apiBaseUrl}/api/papers/${route.params.id}/recommendations?size=6`)
    recommendations.value = recResponse.ok ? await recResponse.json() : []
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to load paper detail.'
    paper.value = null
    recommendations.value = []
  } finally {
    loading.value = false
  }
}

function buildCitation() {
  if (!paper.value) {
    return ''
  }
  const year = paper.value.publicationDate ? paper.value.publicationDate.slice(0, 4) : 'n.d.'
  const journal = paper.value.journal || 'Unknown Journal'
  const doi = paper.value.doi ? ` https://doi.org/${paper.value.doi}` : ''
  return `${paper.value.authors || 'Unknown author'} (${year}). ${paper.value.title}. ${journal}.${doi}`.trim()
}

async function copyCitation() {
  try {
    await navigator.clipboard.writeText(buildCitation())
    showToast('Citation copied')
  } catch (error) {
    showToast('Copy failed, please copy manually')
  }
}

async function copyShareLink() {
  try {
    await navigator.clipboard.writeText(window.location.href)
    showToast('Share link copied')
  } catch (error) {
    showToast('Cannot copy share link')
  }
}

async function toggleFavoriteState() {
  if (!paper.value) {
    return
  }
  const wasFavorite = isFavorite.value
  try {
    await toggleFavorite(paper.value)
    showToast(wasFavorite ? 'Removed from favorites' : 'Added to favorites')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to update favorites')
  }
}

async function toggleReadingState() {
  if (!paper.value) {
    return
  }
  const wasReading = isReading.value
  try {
    await toggleReading(paper.value)
    showToast(wasReading ? 'Removed from reading list' : 'Added to reading list')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to update reading list')
  }
}

function showToast(message) {
  toastMessage.value = message
  if (toastTimer) {
    clearTimeout(toastTimer)
  }
  toastTimer = setTimeout(() => {
    toastMessage.value = ''
  }, 2000)
}

onMounted(fetchPaper)

onMounted(() => {
  const raw = localStorage.getItem(userStorageKey)
  let userId = null
  if (raw) {
    try {
      userId = JSON.parse(raw)?.id || null
    } catch (error) {
      userId = null
    }
  }

  setSession({ apiBaseUrl, userId }).catch(() => {
    showToast('Shelf sync failed, using local data')
  })
})

watch(() => route.params.id, fetchPaper)
</script>

<template>
  <div class="detail-page">
    <header class="detail-topbar">
      <RouterLink class="back-link" to="/">← Back to search</RouterLink>
      <button type="button" class="share-link-button" @click="copyShareLink">Copy share link</button>
    </header>

    <main class="detail-main">
      <p v-if="loading" class="detail-status">Loading paper detail...</p>
      <p v-else-if="errorMessage" class="detail-status error">{{ errorMessage }}</p>

      <article v-else-if="paper" class="detail-card">
        <p class="detail-url">{{ paper.url || 'https://scholar.local/paper/' + paper.id }}</p>
        <h1>{{ paper.title }}</h1>
        <p class="detail-authors">{{ paper.authors }}</p>
        <p class="detail-meta">{{ paper.journal || 'Unknown Journal' }} · {{ paper.publicationDate || 'Unknown date' }}</p>

        <section>
          <h2>Abstract</h2>
          <p>{{ paper.abstractText }}</p>
        </section>

        <section>
          <h2>Identifiers</h2>
          <p><strong>DOI:</strong> {{ paper.doi || 'Not available' }}</p>
          <p><strong>ID:</strong> {{ paper.id }}</p>
        </section>

        <div class="detail-actions">
          <button type="button" @click="toggleFavoriteState">
            {{ isFavorite ? 'Remove favorite' : 'Add to favorites' }}
          </button>
          <button type="button" @click="toggleReadingState">
            {{ isReading ? 'Remove reading list' : 'Add to reading list' }}
          </button>
          <button type="button" @click="copyCitation">Copy citation</button>
          <a v-if="paper.url" :href="paper.url" target="_blank" rel="noreferrer">Open source</a>
        </div>

        <section class="recommendation-section">
          <h2>Recommended papers</h2>
          <ul v-if="recommendations.length" class="recommendation-list">
            <li v-for="item in recommendations" :key="item.id">
              <RouterLink :to="`/paper/${item.id}`">{{ item.title }}</RouterLink>
              <p>{{ item.authors }} · {{ item.journal }}</p>
            </li>
          </ul>
          <p v-else class="detail-status">No recommendations available for this paper yet.</p>
        </section>
      </article>
    </main>

    <transition name="toast-fade">
      <div v-if="toastMessage" class="toast">{{ toastMessage }}</div>
    </transition>
  </div>
</template>

<style scoped>
.detail-page {
  width: min(1000px, calc(100vw - 28px));
  margin: 18px auto 36px;
}

.detail-topbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.back-link,
.share-link-button {
  border: 0;
  border-radius: 999px;
  padding: 10px 14px;
  text-decoration: none;
  color: #1a73e8;
  background: #edf4ff;
  font-weight: 600;
}

.share-link-button {
  cursor: pointer;
}

.detail-main {
  border: 1px solid var(--scholar-border);
  border-radius: 28px;
  background: var(--scholar-card);
  box-shadow: var(--scholar-shadow);
  padding: 28px;
}

.detail-status {
  color: #567396;
}

.detail-status.error {
  color: #b42318;
}

.detail-url {
  color: #188038;
  margin-bottom: 8px;
  word-break: break-all;
}

h1 {
  font-family: var(--font-serif);
  color: #1a0dab;
  font-size: clamp(2rem, 4vw, 3rem);
  line-height: 1.08;
  margin-bottom: 10px;
}

.detail-authors {
  color: #284d78;
}

.detail-meta {
  color: #617791;
  margin-bottom: 16px;
}

section {
  margin-top: 18px;
}

h2 {
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #315882;
  margin-bottom: 8px;
}

section p {
  color: #213b5c;
  line-height: 1.75;
}

.detail-actions {
  margin-top: 24px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.detail-actions button,
.detail-actions a {
  border: 0;
  border-radius: 999px;
  padding: 10px 14px;
  text-decoration: none;
  cursor: pointer;
  background: #edf4ff;
  color: #1a73e8;
  font-weight: 600;
}

.detail-actions button:first-child,
.detail-actions button:nth-child(2) {
  background: linear-gradient(135deg, #1a73e8, #0f9d58);
  color: #fff;
}

.recommendation-section {
  margin-top: 26px;
}

.recommendation-list {
  margin-top: 12px;
  list-style: none;
  display: grid;
  gap: 12px;
}

.recommendation-list li {
  border: 1px solid rgba(125, 150, 185, 0.22);
  border-radius: 14px;
  padding: 12px 14px;
  background: #f8fbff;
}

.recommendation-list a {
  color: #1a0dab;
  font-weight: 600;
}

.recommendation-list p {
  margin-top: 4px;
  color: #5f748f;
  font-size: 13px;
}

.toast {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(21, 38, 63, 0.96);
  color: #fff;
  padding: 10px 14px;
  border-radius: 999px;
  font-size: 14px;
  z-index: 40;
}

.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(8px);
}

@media (max-width: 760px) {
  .detail-main {
    padding: 20px;
    border-radius: 20px;
  }

  .detail-topbar {
    flex-direction: column;
  }
}
</style>

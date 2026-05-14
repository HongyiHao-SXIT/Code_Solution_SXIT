<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import PaperResultItem from '../components/PaperResultItem.vue'
import LoginView from './login.vue'
import { usePaperShelf } from '../composables/usePaperShelf'
import { parseAdvancedQuery } from '../utils/parseAdvancedQuery'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
const userStorageKey = 'academic_scholar_user'

const searchForm = reactive({
  keyword: 'knowledge graph author:"Lin Wang" year:2025',
  author: '',
  journal: '',
  year: '',
})

const meta = reactive({
  total: 0,
  page: 0,
  size: 10,
  totalPages: 0,
})

const currentUser = ref(null)
const loading = ref(false)
const errorMessage = ref('')
const results = ref([])
const showLogin = ref(false)
const sortBy = ref('relevance')
const selectedYear = ref('')
const toastMessage = ref('')
let toastTimer = null

const parsedQuery = ref({ keyword: '', author: '', journal: '', year: '' })

const yearShortcuts = ['2026', '2025', '2024', '2023', '2022', '2021']

const focusTopics = [
  { label: 'Knowledge graph', query: 'knowledge graph' },
  { label: 'LLM retrieval', query: 'language models' },
  { label: 'Scientometrics', query: 'research communities' },
  { label: 'Recommendation', query: 'recommendation' },
]

const {
  favorites,
  readingList,
  favoriteIds,
  readingIds,
  setSession,
  toggleFavorite,
  toggleReading,
  removeFavorite,
  removeReading,
  syncPaper,
} = usePaperShelf()

const summaryText = computed(() => {
  if (loading.value) {
    return 'Searching publications...'
  }
  if (!results.value.length) {
    return 'No matching papers yet. Try a broader query.'
  }
  return `About ${meta.total} results (${meta.page + 1}/${Math.max(meta.totalPages, 1)} pages)`
})

const queryHints = computed(() => {
  const hints = []
  if (parsedQuery.value.author) {
    hints.push(`author:${parsedQuery.value.author}`)
  }
  if (parsedQuery.value.journal) {
    hints.push(`journal:${parsedQuery.value.journal}`)
  }
  if (parsedQuery.value.year) {
    hints.push(`year:${parsedQuery.value.year}`)
  }
  return hints
})

const displayedResults = computed(() => {
  const list = [...results.value]

  if (sortBy.value === 'newest') {
    return list.sort((a, b) => (b.publicationDate || '').localeCompare(a.publicationDate || ''))
  }

  if (sortBy.value === 'oldest') {
    return list.sort((a, b) => (a.publicationDate || '').localeCompare(b.publicationDate || ''))
  }

  return list
})

const featuredResults = computed(() => displayedResults.value.slice(0, 3))

watch(
  currentUser,
  (value) => {
    if (value) {
      localStorage.setItem(userStorageKey, JSON.stringify(value))
      return
    }
    localStorage.removeItem(userStorageKey)
  },
  { deep: true },
)

function buildSearchPayload() {
  const parsed = parseAdvancedQuery(searchForm.keyword)
  parsedQuery.value = parsed

  return {
    keyword: parsed.keyword,
    author: searchForm.author.trim() || parsed.author,
    journal: searchForm.journal.trim() || parsed.journal,
    year: searchForm.year.trim() || parsed.year,
  }
}

async function fetchPapers(page = 0) {
  loading.value = true
  errorMessage.value = ''

  try {
    const payload = buildSearchPayload()
    const params = new URLSearchParams({
      page: String(page),
      size: String(meta.size),
    })

    if (payload.keyword) {
      params.set('keyword', payload.keyword)
    }
    if (payload.author) {
      params.set('author', payload.author)
    }
    if (payload.journal) {
      params.set('journal', payload.journal)
    }
    if (payload.year) {
      params.set('year', payload.year)
    }

    const response = await fetch(`${apiBaseUrl}/api/papers?${params.toString()}`)
    if (!response.ok) {
      throw new Error('Unable to load papers from the backend.')
    }

    const data = await response.json()
    results.value = data.items || []
    results.value.forEach((paper) => syncPaper(paper))

    meta.total = data.total || 0
    meta.page = data.page || 0
    meta.size = data.size || 10
    meta.totalPages = data.totalPages || 0
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Search failed.'
    results.value = []
    meta.total = 0
    meta.page = 0
    meta.totalPages = 0
  } finally {
    loading.value = false
  }
}

function submitSearch() {
  fetchPapers(0)
}

function applyTopic(query) {
  searchForm.keyword = query
  fetchPapers(0)
}

function applyYear(year) {
  selectedYear.value = year
  searchForm.year = year
  fetchPapers(0)
}

function clearYearFilter() {
  selectedYear.value = ''
  searchForm.year = ''
  fetchPapers(0)
}

function changePage(nextPage) {
  if (nextPage < 0 || nextPage >= meta.totalPages) {
    return
  }
  fetchPapers(nextPage)
}

function handleAuthenticated(user) {
  currentUser.value = user
  showLogin.value = false
  setSession({ apiBaseUrl, userId: user.id }).catch(() => {
    showToast('Shelf sync failed, using local data')
  })
  showToast(`Welcome ${user.name || user.account}`)
}

function logout() {
  currentUser.value = null
  setSession({ apiBaseUrl, userId: null }).catch(() => {
    showToast('Switched to local shelf')
  })
  showToast('Signed out')
}

async function handleFavorite(paper) {
  const wasFavorite = favoriteIds.value.has(paper.id)
  try {
    await toggleFavorite(paper)
    showToast(wasFavorite ? 'Removed from favorites' : 'Added to favorites')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to update favorites')
  }
}

async function handleReading(paper) {
  const wasReading = readingIds.value.has(paper.id)
  try {
    await toggleReading(paper)
    showToast(wasReading ? 'Removed from reading list' : 'Added to reading list')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to update reading list')
  }
}

async function removeFavoriteEntry(id) {
  try {
    await removeFavorite(id)
    showToast('Removed from favorites')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to remove favorite')
  }
}

async function removeReadingEntry(id) {
  try {
    await removeReading(id)
    showToast('Removed from reading list')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to remove reading item')
  }
}

async function handleCopyCitation(payload) {
  try {
    await navigator.clipboard.writeText(payload)
    showToast('Citation copied to clipboard')
  } catch (error) {
    showToast('Unable to copy automatically. Please copy manually.')
  }
}

function showToast(message) {
  toastMessage.value = message
  if (toastTimer) {
    clearTimeout(toastTimer)
  }
  toastTimer = setTimeout(() => {
    toastMessage.value = ''
  }, 2200)
}

onMounted(() => {
  const rawUser = localStorage.getItem(userStorageKey)
  if (rawUser) {
    try {
      currentUser.value = JSON.parse(rawUser)
    } catch (error) {
      localStorage.removeItem(userStorageKey)
    }
  }

  setSession({ apiBaseUrl, userId: currentUser.value?.id || null }).catch(() => {
    showToast('Shelf sync failed, using local data')
  })

  fetchPapers(0)
})
</script>

<template>
  <div class="scholar-shell">
    <header class="scholar-header">
      <RouterLink class="brand-block" to="/">
        <div class="brand-mark">A</div>
        <div>
          <p class="brand-name">Academic Scholar</p>
          <p class="brand-caption">Search papers, authors, and research directions</p>
        </div>
      </RouterLink>

      <nav class="header-nav">
        <button
          v-for="topic in focusTopics"
          :key="topic.label"
          class="topic-chip"
          type="button"
          @click="applyTopic(topic.query)"
        >
          {{ topic.label }}
        </button>
      </nav>

      <div class="account-wrap">
        <button v-if="!currentUser" class="account-button" type="button" @click="showLogin = true">
          Sign in
        </button>
        <div v-else class="account-pill">
          <span>{{ currentUser.name || currentUser.account }}</span>
          <button type="button" @click="logout">Logout</button>
        </div>
      </div>
    </header>

    <section class="hero-panel">
      <div class="hero-copy">
        <p class="hero-kicker">Scholar-inspired academic discovery</p>
        <h1>Find citations, trends, and influential papers in one focused search flow.</h1>
        <p class="hero-text">
          Advanced syntax enabled: use tokens like
          <strong>author:"Lin Wang"</strong>,
          <strong>journal:"Knowledge-Based Systems"</strong>,
          <strong>year:2024</strong> directly in the main search box.
        </p>
      </div>

      <form class="search-card" @submit.prevent="submitSearch">
        <div class="search-row search-row-main">
          <input v-model="searchForm.keyword" type="text" placeholder="Search papers, topics, DOI, or author:xxx year:2024" />
          <button type="submit">Search</button>
        </div>

        <div class="search-row search-row-secondary">
          <input v-model="searchForm.author" type="text" placeholder="Author" />
          <input v-model="searchForm.journal" type="text" placeholder="Journal" />
          <input v-model="searchForm.year" type="text" maxlength="4" placeholder="Year" />
        </div>

        <div v-if="queryHints.length" class="query-hints">
          <span v-for="hint in queryHints" :key="hint">{{ hint }}</span>
        </div>
      </form>
    </section>

    <main class="content-grid">
      <aside class="sidebar-panel">
        <section class="sidebar-card">
          <p class="sidebar-title">Search summary</p>
          <p class="sidebar-value">{{ summaryText }}</p>
          <div class="sort-box">
            <label for="sortBy">Sort by</label>
            <select id="sortBy" v-model="sortBy">
              <option value="relevance">Relevance</option>
              <option value="newest">Newest first</option>
              <option value="oldest">Oldest first</option>
            </select>
          </div>
        </section>

        <section class="sidebar-card">
          <p class="sidebar-title">Year filter</p>
          <div class="year-filter-row">
            <button
              v-for="year in yearShortcuts"
              :key="year"
              :class="['year-pill', { active: selectedYear === year }]"
              type="button"
              @click="applyYear(year)"
            >
              {{ year }}
            </button>
          </div>
          <button v-if="selectedYear" class="clear-filter-button" type="button" @click="clearYearFilter">
            Clear year filter
          </button>
        </section>

        <section class="sidebar-card">
          <p class="sidebar-title">Highlighted results</p>
          <ul class="highlight-list">
            <li v-for="paper in featuredResults" :key="paper.id">
              <strong>{{ paper.title }}</strong>
              <span>{{ paper.journal }}</span>
            </li>
          </ul>
        </section>

        <section class="sidebar-card">
          <p class="sidebar-title">My favorites</p>
          <ul v-if="favorites.length" class="shelf-list">
            <li v-for="paper in favorites" :key="`fav-${paper.id}`">
              <RouterLink :to="`/paper/${paper.id}`">{{ paper.title }}</RouterLink>
              <button type="button" @click="removeFavoriteEntry(paper.id)">×</button>
            </li>
          </ul>
          <p v-else class="sidebar-note">No favorites yet.</p>
        </section>

        <section class="sidebar-card">
          <p class="sidebar-title">Reading list</p>
          <ul v-if="readingList.length" class="shelf-list">
            <li v-for="paper in readingList" :key="`reading-${paper.id}`">
              <RouterLink :to="`/paper/${paper.id}`">{{ paper.title }}</RouterLink>
              <button type="button" @click="removeReadingEntry(paper.id)">×</button>
            </li>
          </ul>
          <p v-else class="sidebar-note">No papers in reading list.</p>
        </section>
      </aside>

      <section class="results-panel">
        <div class="results-toolbar">
          <div>
            <p class="results-title">Research results</p>
            <p class="results-subtitle">{{ summaryText }}</p>
          </div>

          <div class="pager-group">
            <button type="button" :disabled="meta.page === 0 || loading" @click="changePage(meta.page - 1)">
              Previous
            </button>
            <button
              type="button"
              :disabled="meta.totalPages === 0 || meta.page >= meta.totalPages - 1 || loading"
              @click="changePage(meta.page + 1)"
            >
              Next
            </button>
          </div>
        </div>

        <p v-if="errorMessage" class="status-message error-message">{{ errorMessage }}</p>
        <p v-else-if="loading" class="status-message">Loading papers from the server...</p>
        <p v-else-if="!results.length" class="status-message">No results returned from the backend.</p>

        <div v-else class="results-list">
          <PaperResultItem
            v-for="paper in displayedResults"
            :key="paper.id"
            :paper="paper"
            :is-favorite="favoriteIds.has(paper.id)"
            :is-reading="readingIds.has(paper.id)"
            @copy-citation="handleCopyCitation"
            @toggle-favorite="handleFavorite"
            @toggle-reading="handleReading"
          />
        </div>
      </section>
    </main>

    <LoginView
      v-if="showLogin"
      :api-base-url="apiBaseUrl"
      @authenticated="handleAuthenticated"
      @close="showLogin = false"
    />

    <transition name="toast-fade">
      <div v-if="toastMessage" class="toast">{{ toastMessage }}</div>
    </transition>
  </div>
</template>

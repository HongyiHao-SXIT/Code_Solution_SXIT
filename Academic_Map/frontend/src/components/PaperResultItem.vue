<script setup>
import { RouterLink } from 'vue-router'

const props = defineProps({
  paper: {
    type: Object,
    required: true,
  },
  isFavorite: {
    type: Boolean,
    default: false,
  },
  isReading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['copy-citation', 'toggle-favorite', 'toggle-reading'])

function buildCitation() {
  const year = props.paper.publicationDate ? props.paper.publicationDate.slice(0, 4) : 'n.d.'
  const journal = props.paper.journal || 'Unknown Journal'
  const doi = props.paper.doi ? ` https://doi.org/${props.paper.doi}` : ''
  return `${props.paper.authors || 'Unknown author'} (${year}). ${props.paper.title}. ${journal}.${doi}`.trim()
}

function copyCitation() {
  emit('copy-citation', buildCitation())
}
</script>

<template>
  <article class="result-card">
    <p class="result-url">{{ paper.url || 'https://scholar.local/paper/' + paper.id }}</p>
    <h3 class="result-title">
      <RouterLink :to="`/paper/${paper.id}`">{{ paper.title }}</RouterLink>
    </h3>
    <p class="result-meta">{{ paper.authors }} · {{ paper.journal || 'Unknown journal' }} · {{ paper.publicationDate || 'Unknown date' }}</p>
    <p class="result-abstract">{{ paper.abstractText }}</p>

    <div class="result-footer">
      <span>DOI: {{ paper.doi || 'Not available' }}</span>
      <div class="result-actions">
        <RouterLink :to="`/paper/${paper.id}`">Details</RouterLink>
        <button type="button" @click="$emit('toggle-favorite', paper)">
          {{ isFavorite ? 'Unfavorite' : 'Favorite' }}
        </button>
        <button type="button" @click="$emit('toggle-reading', paper)">
          {{ isReading ? 'Remove list' : 'Reading list' }}
        </button>
        <button type="button" @click="copyCitation">Cite</button>
        <a v-if="paper.url" class="source-link" :href="paper.url" target="_blank" rel="noreferrer">Open source</a>
      </div>
    </div>
  </article>
</template>

<style scoped>
.result-card {
  padding: 20px 22px;
  border-radius: 22px;
  border: 1px solid rgba(125, 150, 185, 0.18);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(251, 253, 255, 0.94));
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.result-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(25, 53, 89, 0.08);
}

.result-url {
  color: #188038;
  font-size: 13px;
  margin-bottom: 4px;
  word-break: break-all;
}

.result-title {
  font-size: 24px;
  line-height: 1.2;
  font-family: Georgia, 'Times New Roman', serif;
  margin-bottom: 6px;
}

.result-title a {
  color: #1a0dab;
}

.result-title a:hover {
  text-decoration: underline;
}

.result-meta {
  color: #5f6368;
  font-size: 14px;
  margin-bottom: 10px;
}

.result-abstract {
  color: #25374d;
  font-size: 15px;
}

.result-footer {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-top: 14px;
  color: #62748b;
  font-size: 13px;
}

.result-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.result-actions button,
.result-actions a,
.result-footer a {
  border: 0;
  background: #edf4ff;
  color: #1a73e8;
  font-weight: 600;
  padding: 6px 10px;
  border-radius: 999px;
  cursor: pointer;
}

.source-link {
  background: transparent;
  padding: 0;
  color: #1a73e8;
}

.result-actions button:hover,
.result-actions a:hover,
.source-link:hover {
  text-decoration: underline;
}
</style>

<script setup>
const props = defineProps({
  paper: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'copy-citation'])

function citationText() {
  const year = props.paper.publicationDate ? props.paper.publicationDate.slice(0, 4) : 'n.d.'
  const journal = props.paper.journal || 'Unknown Journal'
  const doi = props.paper.doi ? ` https://doi.org/${props.paper.doi}` : ''
  return `${props.paper.authors || 'Unknown author'} (${year}). ${props.paper.title}. ${journal}.${doi}`.trim()
}
</script>

<template>
  <div class="detail-overlay" @click.self="$emit('close')">
    <aside class="detail-drawer">
      <header class="detail-header">
        <p class="detail-kicker">Paper details</p>
        <button type="button" @click="$emit('close')">Close</button>
      </header>

      <h2>{{ paper.title }}</h2>
      <p class="detail-authors">{{ paper.authors }}</p>
      <p class="detail-meta">{{ paper.journal || 'Unknown Journal' }} · {{ paper.publicationDate || 'Unknown date' }}</p>

      <section class="detail-section">
        <h3>Abstract</h3>
        <p>{{ paper.abstractText }}</p>
      </section>

      <section class="detail-section">
        <h3>Identifiers</h3>
        <p><strong>DOI:</strong> {{ paper.doi || 'Not available' }}</p>
        <p><strong>ID:</strong> {{ paper.id }}</p>
      </section>

      <section class="detail-actions">
        <button type="button" @click="$emit('copy-citation', citationText())">Copy citation</button>
        <a v-if="paper.url" :href="paper.url" target="_blank" rel="noreferrer">Open paper link</a>
      </section>
    </aside>
  </div>
</template>

<style scoped>
.detail-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  justify-content: flex-end;
  background: rgba(10, 22, 38, 0.32);
  backdrop-filter: blur(3px);
  z-index: 30;
}

.detail-drawer {
  width: min(100%, 580px);
  height: 100%;
  overflow-y: auto;
  background: #ffffff;
  padding: 28px;
  box-shadow: -14px 0 35px rgba(18, 42, 71, 0.18);
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.detail-kicker {
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-size: 12px;
  color: #5f7ca8;
  font-weight: 700;
}

.detail-header button {
  border: 0;
  background: #edf4ff;
  color: #1a73e8;
  border-radius: 999px;
  padding: 8px 12px;
  cursor: pointer;
}

h2 {
  color: #133151;
  font-size: 30px;
  line-height: 1.18;
  font-family: Georgia, 'Times New Roman', serif;
  margin-bottom: 10px;
}

.detail-authors {
  color: #3f5e83;
  font-size: 15px;
}

.detail-meta {
  color: #617791;
  font-size: 14px;
  margin: 8px 0 20px;
}

.detail-section {
  margin-top: 16px;
}

.detail-section h3 {
  color: #1b436f;
  font-size: 14px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.detail-section p {
  color: #26415f;
  line-height: 1.72;
}

.detail-actions {
  margin-top: 24px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.detail-actions button,
.detail-actions a {
  border: 0;
  border-radius: 999px;
  padding: 10px 14px;
  cursor: pointer;
  text-decoration: none;
}

.detail-actions button {
  background: linear-gradient(135deg, #1a73e8, #0f9d58);
  color: #fff;
}

.detail-actions a {
  background: #edf4ff;
  color: #1a73e8;
  font-weight: 600;
}

@media (max-width: 640px) {
  .detail-drawer {
    padding: 22px;
  }

  h2 {
    font-size: 24px;
  }
}
</style>

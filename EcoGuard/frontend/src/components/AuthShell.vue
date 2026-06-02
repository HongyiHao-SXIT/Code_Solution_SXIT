<script setup>
import '../assets/auth-layout.css'

defineProps({
  kicker: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <section class="auth-shell">
    <div class="auth-surface">
      <aside class="auth-side">
        <p class="auth-kicker">{{ kicker }}</p>
        <h2>{{ title }}</h2>
        <p class="auth-desc">
          {{ description }}
        </p>
      </aside>

      <div class="auth-card">
        <slot name="form" />
        <div class="auth-links">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </section>
</template>
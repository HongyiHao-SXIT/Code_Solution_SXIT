<script setup>
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
    <div class="auth-wrapper">
      <aside class="auth-hero">
        <div class="auth-hero-icon">
          <svg viewBox="0 0 48 46" fill="none"><path fill="#22c55e" d="M25.946 44.938c-.664.845-2.021.375-2.021-.698V33.937a2.26 2.26 0 0 0-2.262-2.262H10.287c-.92 0-1.456-1.04-.92-1.788l7.48-10.471c1.07-1.497 0-3.578-1.842-3.578H1.237c-.92 0-1.456-1.04-.92-1.788L10.013.474c.214-.297.556-.474.92-.474h28.894c.92 0 1.456 1.04.92 1.788l-7.48 10.471c-1.07 1.498 0 3.579 1.842 3.579h11.377c.943 0 1.473 1.088.89 1.83L25.947 44.94z"/></svg>
        </div>
        <p class="auth-kicker">{{ kicker }}</p>
        <h2 class="auth-hero-title">{{ title }}</h2>
        <p class="auth-desc">{{ description }}</p>
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

<style scoped>
.auth-shell {
  width: 100%;
  max-width: 880px;
}

.auth-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  align-items: center;
}

.auth-hero {
  padding: var(--space-3xl);
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.auth-hero-icon {
  width: 48px;
  height: 46px;
  margin-bottom: var(--space-sm);
}

.auth-hero-icon svg {
  width: 100%;
  height: 100%;
}

.auth-kicker {
  font-size: var(--font-size-xs);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--color-accent);
}

.auth-hero-title {
  font-size: var(--font-size-2xl);
  font-weight: 800;
  color: var(--color-text-primary);
  line-height: 1.3;
}

.auth-desc {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
  line-height: 1.7;
}

.auth-card {
  background: var(--color-bg-surface);
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-xl);
  padding: var(--space-2xl) var(--space-xl);
  box-shadow: var(--shadow-lg);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

@media (max-width: 768px) {
  .auth-wrapper {
    grid-template-columns: 1fr;
  }
  .auth-hero {
    text-align: center;
    align-items: center;
    padding: var(--space-xl) var(--space-lg) var(--space-md);
  }
  .auth-hero-title {
    font-size: var(--font-size-xl);
  }
}
</style>
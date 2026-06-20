<script setup lang="ts">
</script>

<template>
  <footer class="app-footer">
    <div class="footer-inner container">
      <div class="footer-brand">
        <span class="footer-logo">◈</span>
        <span>Today UP!</span>
      </div>
      <p class="footer-copy">&copy; {{ new Date().getFullYear() }} Today UP! All rights reserved.</p>
      <div class="footer-links">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Contact</a>
      </div>
    </div>
  </footer>
</template>

<style lang="css" scoped>
.app-footer {
  background: rgba(10, 10, 30, 0.6);
  backdrop-filter: blur(var(--glass-blur, 16px));
  -webkit-backdrop-filter: blur(var(--glass-blur, 16px));
  border-top: 1px solid var(--border-color);
  margin-top: auto;
}

.footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
  flex-wrap: wrap;
  gap: 12px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 500;
}

.footer-logo {
  color: var(--primary-color);
  font-size: 16px;
  text-shadow: 0 0 8px var(--primary-glow);
}

.footer-copy {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}

.footer-links {
  display: flex;
  gap: 16px;
}

.footer-links a {
  text-decoration: none;
  color: var(--text-muted);
  font-size: 12px;
  transition: color 0.25s ease;
}

.footer-links a:hover {
  color: var(--primary-color);
}
</style>
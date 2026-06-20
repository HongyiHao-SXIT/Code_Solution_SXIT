<script setup lang="ts">
</script>

<template>
  <header class="app-header">
    <div class="header-inner container">
      <router-link to="/" class="logo">
        <span class="logo-icon">◈</span>
        <span class="logo-text">Today <span class="logo-highlight">UP!</span></span>
      </router-link>

      <nav class="nav-menu">
        <router-link to="/" class="nav-link">Home</router-link>
        <router-link to="/tasks" class="nav-link">Tasks</router-link>
        <router-link to="/study-plans" class="nav-link">Study Plans</router-link>
        <router-link to="/weekly-review" class="nav-link">Weekly Review</router-link>
        <router-link to="/profile" class="nav-link">Profile</router-link>
      </nav>

      <div class="auth-links">
        <router-link to="/login" class="btn-ghost">Login</router-link>
        <router-link to="/register" class="btn-primary">Register</router-link>
      </div>
    </div>
  </header>
</template>

<style lang="css" scoped>
.app-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(10, 10, 30, 0.85);
  backdrop-filter: blur(var(--glass-blur, 16px));
  -webkit-backdrop-filter: blur(var(--glass-blur, 16px));
  border-bottom: 1px solid var(--border-color);
}

.header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}

.logo-icon {
  font-size: 26px;
  color: var(--primary-color);
  text-shadow: 0 0 12px var(--primary-glow);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}

.logo-text {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-color);
  letter-spacing: 0.5px;
}

.logo-highlight {
  color: var(--primary-color);
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 4px;
}

.nav-link {
  text-decoration: none;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 500;
  padding: 8px 14px;
  border-radius: 8px;
  transition: all 0.25s ease;
  position: relative;
}

.nav-link:hover {
  color: var(--primary-color);
  background: rgba(0, 212, 255, 0.05);
}

.nav-link.router-link-exact-active {
  color: var(--primary-color);
  background: rgba(0, 212, 255, 0.08);
}

.nav-link.router-link-exact-active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 2px;
  background: var(--primary-color);
  border-radius: 1px;
  box-shadow: 0 0 8px var(--primary-glow);
}

.auth-links {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-ghost {
  text-decoration: none;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 500;
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid rgba(148, 163, 184, 0.2);
  transition: all 0.25s ease;
}

.btn-ghost:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
  box-shadow: 0 0 12px rgba(0, 212, 255, 0.1);
}

.btn-primary {
  text-decoration: none;
  color: #0a0a1a;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 18px;
  border-radius: 8px;
  background: var(--primary-color);
  transition: all 0.25s ease;
  box-shadow: 0 0 16px rgba(0, 212, 255, 0.25);
}

.btn-primary:hover {
  box-shadow: 0 0 24px rgba(0, 212, 255, 0.45);
  transform: translateY(-1px);
}
</style>
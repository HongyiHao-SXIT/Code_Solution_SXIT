<script setup lang="ts">
import { ref } from 'vue'

const email = ref('')
const password = ref('')
const rememberMe = ref(false)

const handleLogin = () => {
  // TODO: Implement actual login logic
  console.log('Login:', { email: email.value, rememberMe: rememberMe.value })
  alert('Welcome back, commander!')
}
</script>

<template>
  <div class="auth-layout">
    <div class="auth-particles">
      <div class="particle p1"></div>
      <div class="particle p2"></div>
      <div class="particle p3"></div>
      <div class="particle p4"></div>
    </div>

    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-icon">
          <span>◈</span>
        </div>
        <h1>Welcome Back</h1>
        <p class="auth-subtitle">Sign in to your command center</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <span class="input-icon">✉</span>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="your@email.com"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input
              id="password"
              v-model="password"
              type="password"
              placeholder="Enter your password"
              required
            />
          </div>
        </div>

        <div class="form-row">
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input v-model="rememberMe" type="checkbox" />
              <span class="checkmark"></span>
              Remember me
            </label>
          </div>
          <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn-glow">
          <span>Sign In</span>
          <span class="btn-arrow">→</span>
        </button>
      </form>

      <p class="auth-footer">
        Don't have an account?
        <router-link to="/register">Create one</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 120px);
  padding: 40px 20px;
  position: relative;
  overflow: hidden;
}

.auth-particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.particle {
  position: absolute;
  border-radius: 50%;
  filter: blur(40px);
  opacity: 0.15;
}

.p1 {
  width: 300px;
  height: 300px;
  background: var(--primary-color);
  top: -80px;
  right: -60px;
  animation: float1 8s ease-in-out infinite;
}

.p2 {
  width: 200px;
  height: 200px;
  background: var(--secondary-color);
  bottom: -40px;
  left: -40px;
  animation: float2 10s ease-in-out infinite;
}

.p3 {
  width: 150px;
  height: 150px;
  background: var(--accent-color);
  top: 50%;
  left: 10%;
  animation: float3 6s ease-in-out infinite;
}

.p4 {
  width: 100px;
  height: 100px;
  background: var(--primary-color);
  bottom: 20%;
  right: 20%;
  animation: float2 12s ease-in-out infinite;
}

@keyframes float1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-30px, 30px) scale(1.2); }
}

@keyframes float2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(30px, -20px) scale(1.15); }
}

@keyframes float3 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-20px, -30px) scale(1.3); }
}

.auth-card {
  background: var(--bg-card);
  backdrop-filter: blur(var(--glass-blur, 16px));
  -webkit-backdrop-filter: blur(var(--glass-blur, 16px));
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 40px;
  width: 100%;
  max-width: 440px;
  position: relative;
  z-index: 1;
}

.auth-header {
  text-align: center;
  margin-bottom: 32px;
}

.auth-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 16px;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  box-shadow: 0 0 32px var(--primary-glow);
}

.auth-card h1 {
  font-size: 26px;
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 8px 0;
}

.auth-subtitle {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0;
}

.form-group {
  margin-bottom: 18px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-muted);
  margin-bottom: 6px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  font-size: 14px;
  color: var(--text-muted);
  pointer-events: none;
}

.input-wrapper input {
  width: 100%;
  padding: 12px 14px 12px 40px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  font-size: 14px;
  color: var(--text-color);
  transition: all 0.25s ease;
}

.input-wrapper input::placeholder {
  color: rgba(148, 163, 184, 0.5);
}

.input-wrapper input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1), 0 0 20px rgba(0, 212, 255, 0.05);
}

.form-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-muted);
  cursor: pointer;
}

.checkbox-label input[type='checkbox'] {
  appearance: none;
  width: 18px;
  height: 18px;
  border: 1px solid var(--border-color);
  border-radius: 4px;
  background: rgba(15, 23, 42, 0.6);
  cursor: pointer;
  position: relative;
  flex-shrink: 0;
  transition: all 0.25s ease;
}

.checkbox-label input[type='checkbox']:checked {
  background: var(--primary-color);
  border-color: var(--primary-color);
  box-shadow: 0 0 12px var(--primary-glow);
}

.checkbox-label input[type='checkbox']:checked::after {
  content: '✓';
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0a0a1a;
  font-size: 12px;
  font-weight: 700;
}

.forgot-link {
  font-size: 13px;
  color: var(--primary-color);
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
}

.btn-glow {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 0 24px var(--primary-glow);
}

.btn-glow:hover {
  box-shadow: 0 0 40px var(--primary-glow), 0 0 60px var(--secondary-glow);
  transform: translateY(-2px);
}

.btn-arrow {
  transition: transform 0.3s ease;
}

.btn-glow:hover .btn-arrow {
  transform: translateX(4px);
}

.auth-footer {
  text-align: center;
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 24px;
}

.auth-footer a {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 500;
}

.auth-footer a:hover {
  text-decoration: underline;
}
</style>
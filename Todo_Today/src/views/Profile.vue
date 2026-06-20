<script setup lang="ts">
import { ref } from 'vue'
import { useStorage } from '@/composables/useStorage'

const username = useStorage<string>('profile-username', 'Commander')
const email = useStorage<string>('profile-email', 'commander@todayup.dev')
const bio = useStorage<string>('profile-bio', '')
const isEditing = ref(false)

const toggleEdit = () => {
  isEditing.value = !isEditing.value
}

const saveProfile = () => {
  isEditing.value = false
  alert('Profile updated successfully!')
}
</script>

<template>
  <div class="container page">
    <header class="page-header">
      <div>
        <h1>Profile</h1>
        <p class="page-subtitle">Your command center configuration</p>
      </div>
    </header>

    <div class="profile-card">
      <div class="profile-header">
        <div class="avatar">
          <span>{{ username.charAt(0).toUpperCase() }}</span>
          <div class="avatar-ring"></div>
        </div>

        <div v-if="!isEditing" class="profile-info">
          <h2>{{ username }}</h2>
          <p class="email">{{ email }}</p>
          <p v-if="bio" class="bio">{{ bio }}</p>
          <p v-else class="bio empty">No bio set. Add one to personalize your profile.</p>
          <div class="badge-list">
            <span class="badge">Pro</span>
            <span class="badge secondary">Verified</span>
          </div>
        </div>
      </div>

      <div v-if="!isEditing" class="profile-actions">
        <button class="btn-glow" @click="toggleEdit">Edit Profile</button>
      </div>

      <form v-else class="edit-form" @submit.prevent="saveProfile">
        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrapper">
            <span class="input-icon">@</span>
            <input id="username" v-model="username" type="text" required />
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <span class="input-icon">✉</span>
            <input id="email" v-model="email" type="email" required />
          </div>
        </div>

        <div class="form-group">
          <label for="bio">Bio</label>
          <div class="input-wrapper textarea-wrapper">
            <span class="input-icon top">📝</span>
            <textarea id="bio" v-model="bio" rows="3" placeholder="Tell us about yourself..."></textarea>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-glow">Save Changes</button>
          <button type="button" class="btn-ghost" @click="toggleEdit">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.page {
  padding: 40px 0;
}

.page-header {
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

.profile-card {
  background: var(--bg-card);
  backdrop-filter: blur(var(--glass-blur, 16px));
  -webkit-backdrop-filter: blur(var(--glass-blur, 16px));
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 40px;
  max-width: 560px;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 32px;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  font-weight: 700;
  position: relative;
  flex-shrink: 0;
}

.avatar-ring {
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  border: 2px solid transparent;
  border-top-color: var(--primary-color);
  border-right-color: var(--secondary-color);
  animation: spin 3s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.profile-info h2 {
  font-size: 22px;
  color: var(--text-color);
  font-weight: 700;
  margin: 0 0 4px 0;
}

.email {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0 0 8px 0;
}

.bio {
  font-size: 14px;
  color: var(--text-color);
  line-height: 1.6;
  margin: 0 0 12px 0;
}

.bio.empty {
  color: var(--text-muted);
  font-style: italic;
  opacity: 0.6;
}

.badge-list {
  display: flex;
  gap: 8px;
}

.badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  background: rgba(0, 212, 255, 0.12);
  color: var(--primary-color);
  border: 1px solid rgba(0, 212, 255, 0.2);
}

.badge.secondary {
  background: rgba(124, 58, 237, 0.12);
  color: var(--secondary-color);
  border-color: rgba(124, 58, 237, 0.2);
}

.profile-actions {
  text-align: center;
}

.form-group {
  margin-bottom: 20px;
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
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
  color: var(--text-muted);
  pointer-events: none;
}

.input-icon.top {
  top: 14px;
  transform: none;
}

.input-wrapper input,
.input-wrapper textarea {
  width: 100%;
  padding: 12px 14px 12px 40px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  font-size: 14px;
  color: var(--text-color);
  transition: all 0.25s ease;
  font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
}

.input-wrapper textarea {
  resize: vertical;
  min-height: 80px;
  padding-left: 42px;
}

.input-wrapper input::placeholder,
.input-wrapper textarea::placeholder {
  color: rgba(148, 163, 184, 0.5);
}

.input-wrapper input:focus,
.input-wrapper textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1), 0 0 20px rgba(0, 212, 255, 0.05);
}

.form-actions {
  display: flex;
  gap: 10px;
}

.btn-glow {
  flex: 1;
  padding: 12px 24px;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 0 20px var(--primary-glow);
}

.btn-glow:hover {
  box-shadow: 0 0 36px var(--primary-glow);
  transform: translateY(-1px);
}

.btn-ghost {
  flex: 1;
  padding: 12px 24px;
  background: transparent;
  color: var(--text-muted);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.25s ease;
}

.btn-ghost:hover {
  color: var(--text-color);
  border-color: rgba(148, 163, 184, 0.4);
}
</style>
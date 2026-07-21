<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  apiBaseUrl: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['close', 'authenticated'])

const mode = ref('login')
const loading = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})

const form = reactive({
  name: '',
  account: '',
  password: '',
  email: '',
  affiliation: '',
  gender: 'UNKNOWN',
})

function validateFields() {
  const errors = {}
  if (!form.account.trim()) {
    errors.account = 'Account is required'
  } else if (form.account.trim().length < 2) {
    errors.account = 'Account must be at least 2 characters'
  }

  if (!form.password.trim()) {
    errors.password = 'Password is required'
  } else if (form.password.trim().length < 3) {
    errors.password = 'Password must be at least 3 characters'
  }

  if (mode.value === 'register') {
    if (!form.name.trim()) {
      errors.name = 'Full name is required'
    }
    if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
      errors.email = 'Invalid email format'
    }
  }

  fieldErrors.value = errors
  return Object.keys(errors).length === 0
}

async function submit() {
  if (!validateFields()) {
    return
  }

  loading.value = true
  errorMessage.value = ''

  const endpoint = mode.value === 'login' ? '/api/users/login' : '/api/users/register'
  const payload = mode.value === 'login'
    ? { account: form.account.trim(), password: form.password.trim() }
    : {
        name: form.name.trim(),
        account: form.account.trim(),
        password: form.password.trim(),
        email: form.email.trim(),
        affiliation: form.affiliation.trim(),
        gender: form.gender,
      }

  try {
    const response = await fetch(`${props.apiBaseUrl}${endpoint}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.message || 'Authentication failed.')
    }

    emit('authenticated', data)
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Authentication failed.'
  } finally {
    loading.value = false
  }
}

function switchMode(newMode) {
  mode.value = newMode
  errorMessage.value = ''
  fieldErrors.value = {}
}

function clearFieldError(field) {
  if (fieldErrors.value[field]) {
    delete fieldErrors.value[field]
  }
}
</script>

<template>
  <div class="overlay" @click.self="$emit('close')">
    <section class="dialog-card">
      <div class="dialog-head">
        <div>
          <p class="dialog-kicker">Account access</p>
          <h2>{{ mode === 'login' ? 'Sign in to Academic Scholar' : 'Create a researcher profile' }}</h2>
        </div>
        <button class="close-button" type="button" @click="$emit('close')">×</button>
      </div>

      <div class="switch-row">
        <button :class="['switch-button', { active: mode === 'login' }]" type="button" @click="switchMode('login')">
          Login
        </button>
        <button :class="['switch-button', { active: mode === 'register' }]" type="button" @click="switchMode('register')">
          Register
        </button>
      </div>

      <form class="dialog-form" @submit.prevent="submit">
        <div v-if="mode === 'register'" class="field-group">
          <input
            v-model="form.name"
            type="text"
            placeholder="Full name"
            :class="{ 'input-error': fieldErrors.name }"
            @input="clearFieldError('name')"
          />
          <p v-if="fieldErrors.name" class="field-error-text">{{ fieldErrors.name }}</p>
        </div>

        <div class="field-group">
          <input
            v-model="form.account"
            type="text"
            placeholder="Account"
            :class="{ 'input-error': fieldErrors.account }"
            @input="clearFieldError('account')"
          />
          <p v-if="fieldErrors.account" class="field-error-text">{{ fieldErrors.account }}</p>
        </div>

        <div class="field-group">
          <input
            v-model="form.password"
            type="password"
            placeholder="Password"
            :class="{ 'input-error': fieldErrors.password }"
            @input="clearFieldError('password')"
          />
          <p v-if="fieldErrors.password" class="field-error-text">{{ fieldErrors.password }}</p>
        </div>

        <div v-if="mode === 'register'" class="field-group">
          <input
            v-model="form.email"
            type="email"
            placeholder="Email"
            :class="{ 'input-error': fieldErrors.email }"
            @input="clearFieldError('email')"
          />
          <p v-if="fieldErrors.email" class="field-error-text">{{ fieldErrors.email }}</p>
        </div>

        <input v-if="mode === 'register'" v-model="form.affiliation" type="text" placeholder="Affiliation" />

        <select v-if="mode === 'register'" v-model="form.gender">
          <option value="UNKNOWN">Gender</option>
          <option value="MALE">Male</option>
          <option value="FEMALE">Female</option>
        </select>

        <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>

        <button class="submit-button" type="submit" :disabled="loading">
          {{ loading ? 'Submitting...' : mode === 'login' ? 'Login' : 'Create account' }}
        </button>

        <p v-if="mode === 'login'" class="hint-text">
          Demo account: <strong>demo</strong> / <strong>demo123</strong>
        </p>
      </form>
    </section>
  </div>
</template>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(15, 23, 42, 0.34);
  backdrop-filter: blur(6px);
  padding: 24px;
  z-index: 20;
}

.dialog-card {
  width: min(100%, 480px);
  border-radius: 24px;
  background: #ffffff;
  padding: 28px;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
}

.dialog-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.dialog-kicker {
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #5f7ca8;
  margin-bottom: 6px;
}

.dialog-head h2 {
  color: #183153;
  font-size: 28px;
  line-height: 1.15;
}

.close-button {
  border: 0;
  background: transparent;
  color: #4f607a;
  font-size: 28px;
  cursor: pointer;
}

.switch-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  margin: 24px 0 18px;
}

.switch-button,
.submit-button {
  border: 0;
  border-radius: 14px;
  cursor: pointer;
}

.switch-button {
  padding: 12px 14px;
  background: #eef3fb;
  color: #4e6487;
  font-weight: 600;
}

.switch-button.active {
  background: #2a73d9;
  color: #ffffff;
}

.dialog-form {
  display: grid;
  gap: 12px;
}

.field-group {
  display: grid;
  gap: 4px;
}

.dialog-form input,
.dialog-form select {
  width: 100%;
  border: 1px solid #d4e0f3;
  border-radius: 14px;
  padding: 14px 16px;
  font-size: 15px;
  color: #1b3150;
  background: #fbfdff;
}

.dialog-form input:focus,
.dialog-form select:focus {
  outline: none;
  border-color: #2a73d9;
  box-shadow: 0 0 0 4px rgba(42, 115, 217, 0.12);
}

.input-error {
  border-color: #b42318 !important;
}

.field-error-text {
  color: #b42318;
  font-size: 12px;
  padding-left: 4px;
}

.submit-button {
  margin-top: 8px;
  padding: 14px 18px;
  background: linear-gradient(135deg, #2a73d9, #0f9d58);
  color: #ffffff;
  font-size: 15px;
  font-weight: 700;
}

.submit-button:disabled {
  opacity: 0.7;
  cursor: progress;
}

.error-text {
  color: #b42318;
  font-size: 14px;
}

.hint-text {
  color: #7a8fab;
  font-size: 13px;
  text-align: center;
  margin-top: 4px;
}

@media (max-width: 640px) {
  .dialog-card {
    padding: 22px;
    border-radius: 20px;
  }

  .dialog-head h2 {
    font-size: 24px;
  }
}
</style>
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

const form = reactive({
	name: '',
	account: '',
	password: '',
	email: '',
	affiliation: '',
	gender: 'UNKNOWN',
})

async function submit() {
	loading.value = true
	errorMessage.value = ''

	const endpoint = mode.value === 'login' ? '/api/users/login' : '/api/users/register'
	const payload = mode.value === 'login'
		? { account: form.account, password: form.password }
		: {
				name: form.name,
				account: form.account,
				password: form.password,
				email: form.email,
				affiliation: form.affiliation,
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
				<button :class="['switch-button', { active: mode === 'login' }]" type="button" @click="mode = 'login'">
					Login
				</button>
				<button :class="['switch-button', { active: mode === 'register' }]" type="button" @click="mode = 'register'">
					Register
				</button>
			</div>

			<form class="dialog-form" @submit.prevent="submit">
				<input v-if="mode === 'register'" v-model="form.name" type="text" placeholder="Full name" />
				<input v-model="form.account" type="text" placeholder="Account" />
				<input v-model="form.password" type="password" placeholder="Password" />
				<input v-if="mode === 'register'" v-model="form.email" type="email" placeholder="Email" />
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
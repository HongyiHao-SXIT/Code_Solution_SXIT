<script setup>
import { onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCaptchaChallenge } from '../composables/useCaptchaChallenge'
import { setSessionUser, sessionState } from '../stores/session'
import { useAuthSubmission } from '../composables/useAuthSubmission'
import { firstValidationError, validateTextField } from '../lib/formValidation'
import AuthShell from '../components/AuthShell.vue'
import CaptchaField from '../components/CaptchaField.vue'
import FormField from '../components/FormField.vue'

const route = useRoute()
const router = useRouter()
const form = reactive({
  username: '',
  password: '',
  captcha_text: '',
})

const {
  captcha,
  captchaLoading,
  cooldownLeft,
  loadCaptcha,
  handleCaptchaError,
} = useCaptchaChallenge(form)

const { loading, submit: submitAuth } = useAuthSubmission({
  endpoint: '/api/web/login',
  successMessage: '登录成功',
  errorMessage: '登录失败',
  onSuccess: async (payload) => {
    setSessionUser(payload.user)
    await router.replace(payload.next || '/')
  },
  onError: handleCaptchaError,
})

async function submit() {
  const validationError = firstValidationError(
    validateTextField(form.username, '用户名', { min: 3, max: 50 }),
    validateTextField(form.password, '密码', { min: 6, max: 128 }),
    captcha.enabled ? validateTextField(form.captcha_text, '验证码', { min: 1, max: 8 }) : null,
  )
  if (validationError) {
    return
  }

  await submitAuth({
    ...form,
    captcha_id: captcha.id,
    next: route.query.next || sessionState.nextPath,
  })
}

onMounted(() => {
  loadCaptcha()
})
</script>

<template>
  <AuthShell
    kicker="Welcome Back"
    title="登录管理平台"
    description="实时查看垃圾识别结果、机器人状态和热点分析，继续你的 EcoGuard 管理流程。"
  >
    <template #form>
      <form class="auth-form" @submit.prevent="submit">
        <FormField id="username" v-model.trim="form.username" label="用户名" :maxlength="50" :required="true" />
        <FormField id="password" v-model.trim="form.password" label="密码" type="password" :minlength="6"
          :required="true" />
        <CaptchaField
          v-if="captcha.enabled"
          v-model="form.captcha_text"
          :loading="captchaLoading"
          :image-src="captcha.image_data"
          @refresh="loadCaptcha"
        />
        <button type="submit" class="auth-submit" :disabled="loading || cooldownLeft > 0">
          {{ loading ? '登录中...' : (cooldownLeft > 0 ? `请稍候 ${cooldownLeft}s` : '登录') }}
        </button>
      </form>
    </template>

    <template #footer>
      还没有账号？<RouterLink to="/register">立即注册</RouterLink>
    </template>
  </AuthShell>
</template>

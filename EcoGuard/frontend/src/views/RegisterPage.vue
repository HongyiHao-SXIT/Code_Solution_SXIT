<script setup>
import { onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useCaptchaChallenge } from '../composables/useCaptchaChallenge'
import { useAuthSubmission } from '../composables/useAuthSubmission'
import { firstValidationError, validatePasswordPair, validateTextField } from '../lib/formValidation'
import AuthShell from '../components/AuthShell.vue'
import CaptchaField from '../components/CaptchaField.vue'
import FormField from '../components/FormField.vue'

const router = useRouter()
const form = reactive({
  username: '',
  organization: '',
  password: '',
  confirm_password: '',
  security_code: '',
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
  endpoint: '/api/web/register',
  successMessage: '注册成功，请登录',
  errorMessage: '注册失败',
  onSuccess: async () => {
    await router.push('/login')
  },
  onError: handleCaptchaError,
})

async function submit() {
  const validationError = firstValidationError(
    validateTextField(form.username, '用户名', { min: 3, max: 50 }),
    validateTextField(form.organization, '所属单位', { min: 1, max: 120 }),
    validateTextField(form.security_code, '安全码', { min: 4, max: 32 }),
    validatePasswordPair(form.password, form.confirm_password, { label: '密码', min: 6, max: 128 }),
    captcha.enabled ? validateTextField(form.captcha_text, '验证码', { min: 1, max: 8 }) : null,
  )
  if (validationError) {
    return
  }

  await submitAuth({
    ...form,
    captcha_id: captcha.id,
  })
}

onMounted(() => {
  loadCaptcha()
})
</script>

<template>
  <AuthShell
    kicker="Create Account"
    title="注册管理平台账号"
    description="创建管理账号后即可使用上传检测、机器人管理和统计分析等完整功能。"
  >
    <template #form>
      <form class="auth-form" @submit.prevent="submit">
        <FormField id="username" v-model.trim="form.username" label="用户名" :minlength="3" :maxlength="50"
          :required="true" />
        <FormField id="organization" v-model.trim="form.organization" label="所属单位" placeholder="例如：XX 环卫中心"
          :maxlength="120" :required="true" />
        <FormField id="password" v-model.trim="form.password" label="密码" type="password" :minlength="6"
          :required="true" />
        <FormField id="confirm_password" v-model.trim="form.confirm_password" label="确认密码" type="password"
          :minlength="6" :required="true" />
        <FormField id="security_code" v-model.trim="form.security_code" label="安全码（用于后续身份校验）"
          type="password" :minlength="4" :maxlength="32" :required="true" />
        <CaptchaField
          v-if="captcha.enabled"
          v-model="form.captcha_text"
          :loading="captchaLoading"
          :image-src="captcha.image_data"
          @refresh="loadCaptcha"
        />
        <button type="submit" class="auth-submit" :disabled="loading || cooldownLeft > 0">
          {{ loading ? '注册中...' : (cooldownLeft > 0 ? `请稍候 ${cooldownLeft}s` : '注册') }}
        </button>
      </form>
    </template>

    <template #footer>
      已有账号？<RouterLink to="/login">去登录</RouterLink>
    </template>
  </AuthShell>
</template>

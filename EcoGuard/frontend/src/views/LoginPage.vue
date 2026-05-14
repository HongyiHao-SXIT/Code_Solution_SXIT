<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { postJson } from '../lib/api'
import { useCaptchaChallenge } from '../composables/useCaptchaChallenge'
import { pushFlash, setSessionUser, sessionState } from '../stores/session'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
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

async function submit() {
  loading.value = true
  try {
    const payload = await postJson('/api/web/login', {
      ...form,
      captcha_id: captcha.id,
      next: route.query.next || sessionState.nextPath,
    })
    setSessionUser(payload.user)
    pushFlash(payload.message || '登录成功', 'success')
    await router.replace(payload.next || '/robot')
  } catch (error) {
    pushFlash(error.message || '登录失败', 'error')
    await handleCaptchaError(error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCaptcha()
})
</script>

<template>
  <section class="auth-shell">
    <div class="auth-surface">
      <aside class="auth-side">
        <p class="auth-kicker">Welcome Back</p>
        <h2>登录管理平台</h2>
        <p class="auth-desc">
          实时查看垃圾识别结果、机器人状态和热点分析，继续你的 EcoGuard 管理流程。
        </p>
      </aside>

      <div class="auth-card">
        <form class="auth-form" @submit.prevent="submit">
          <div class="field-block">
            <label for="username">用户名</label>
            <input id="username" v-model.trim="form.username" type="text" maxlength="50" required>
          </div>
          <div class="field-block">
            <label for="password">密码</label>
            <input id="password" v-model.trim="form.password" type="password" minlength="6" required>
          </div>
          <div v-if="captcha.enabled" class="field-block">
            <label for="captcha_text">图形验证码</label>
            <div class="captcha-row">
              <input id="captcha_text" v-model.trim="form.captcha_text" type="text" maxlength="8" required placeholder="请输入验证码">
              <img
                v-if="captcha.image_data"
                :src="captcha.image_data"
                alt="captcha"
                class="captcha-image"
              >
              <button type="button" class="captcha-refresh" :disabled="captchaLoading" @click="loadCaptcha">
                {{ captchaLoading ? '加载中...' : '刷新' }}
              </button>
            </div>
          </div>
          <button type="submit" class="auth-submit" :disabled="loading || cooldownLeft > 0">
            {{ loading ? '登录中...' : (cooldownLeft > 0 ? `请稍候 ${cooldownLeft}s` : '登录') }}
          </button>
        </form>

        <div class="auth-links">
          还没有账号？<RouterLink to="/register">立即注册</RouterLink>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.auth-shell {
  min-height: calc(100vh - 126px);
  width: 100%;
  display: block;
  padding: 8px 0;
}

.auth-surface {
  width: 100%;
  min-height: calc(100vh - 142px);
  display: grid;
  grid-template-columns: minmax(280px, 0.42fr) minmax(0, 0.58fr);
  align-items: stretch;
  border-radius: 20px;
  border: 1px solid rgba(46, 122, 104, 0.24);
  background: linear-gradient(140deg, rgba(248, 253, 250, 0.93), rgba(235, 246, 239, 0.92));
  box-shadow: 0 20px 42px rgba(38, 101, 83, 0.18);
  overflow: hidden;
}

.auth-side {
  padding: 30px 28px;
  background:
    radial-gradient(circle at 20% 10%, rgba(54, 170, 145, 0.18), transparent 44%),
    radial-gradient(circle at 75% 85%, rgba(225, 152, 71, 0.15), transparent 42%);
  border-right: 1px solid rgba(83, 151, 130, 0.2);
}

.auth-kicker {
  margin: 0 0 8px;
  font-size: 12px;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  color: #27765f;
}

.auth-side h2 {
  margin: 0;
  font-size: clamp(22px, 2.4vw, 30px);
  color: #1e4f44;
}

.auth-desc {
  margin-top: 12px;
  line-height: 1.65;
  color: #4f746b;
}

.auth-card {
  width: 100%;
  padding: 30px 28px;
  background: linear-gradient(180deg, rgba(252, 255, 253, 0.74), rgba(243, 250, 246, 0.68));
}

.auth-form {
  display: grid;
  gap: 12px;
}

.field-block {
  display: grid;
  gap: 6px;
}

.field-block label {
  color: #315f54;
  font-size: 13px;
}

.captcha-row {
  display: grid;
  grid-template-columns: minmax(120px, 1fr) auto auto;
  gap: 8px;
  align-items: center;
}

.captcha-image {
  height: 40px;
  border-radius: 8px;
  border: 1px solid rgba(73, 146, 125, 0.32);
  background: rgba(255, 255, 255, 0.95);
}

.captcha-refresh {
  height: 40px;
  padding: 0 12px;
  border-radius: 10px;
}

.auth-submit {
  margin-top: 4px;
}

.auth-links {
  margin-top: 12px;
  color: #567c72;
  font-size: 13px;
}

.auth-links a {
  color: #1d856a;
}

@media (max-width: 900px) {
  .auth-shell {
    min-height: auto;
    padding: 6px 0;
  }

  .auth-surface {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  .auth-side {
    border-right: 0;
    border-bottom: 1px solid rgba(120, 198, 205, 0.2);
    padding: 20px 18px;
  }

  .auth-card {
    padding: 20px 18px;
  }

  .captcha-row {
    grid-template-columns: 1fr;
  }

  .captcha-refresh,
  .captcha-image {
    width: 100%;
  }
}
</style>

<script setup>
defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  imageSrc: {
    type: String,
    default: '',
  },
  maxlength: {
    type: [String, Number],
    default: 8,
  },
  placeholder: {
    type: String,
    default: '请输入验证码',
  },
})

const emit = defineEmits(['update:modelValue', 'refresh'])

function updateValue(event) {
  emit('update:modelValue', String(event.target.value ?? '').trim())
}

function refresh() {
  emit('refresh')
}
</script>

<template>
  <div class="field-block">
    <label for="captcha_text">图形验证码</label>
    <div class="captcha-row">
      <input
        id="captcha_text"
        :value="modelValue"
        type="text"
        :maxlength="maxlength"
        required
        :placeholder="placeholder"
        @input="updateValue"
      >
      <img v-if="imageSrc" :src="imageSrc" alt="captcha" class="captcha-image">
      <button type="button" class="captcha-refresh" :disabled="loading" @click="refresh">
        {{ loading ? '加载中...' : '刷新' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.captcha-row {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}
.captcha-row input {
  flex: 1;
  min-width: 0;
}
.captcha-image {
  width: 120px;
  height: 44px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  object-fit: cover;
  flex-shrink: 0;
}
.captcha-refresh {
  min-height: 38px;
  padding: 0 var(--space-md);
  font-size: var(--font-size-xs);
  font-weight: 600;
  flex-shrink: 0;
}
</style>

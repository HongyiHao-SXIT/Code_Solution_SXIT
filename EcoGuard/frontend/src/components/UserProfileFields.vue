<script setup>
import FormField from './FormField.vue'

defineProps({
  form: {
    type: Object,
    required: true,
  },
  includeSecurityCode: {
    type: Boolean,
    default: false,
  },
  includeRole: {
    type: Boolean,
    default: false,
  },
  requirePassword: {
    type: Boolean,
    default: false,
  },
  passwordLabel: {
    type: String,
    default: '密码',
  },
  confirmPasswordLabel: {
    type: String,
    default: '确认密码',
  },
  passwordPlaceholder: {
    type: String,
    default: '',
  },
  confirmPasswordPlaceholder: {
    type: String,
    default: '',
  },
  roleOptions: {
    type: Array,
    default: () => ([
      { value: 'user', label: '普通用户' },
      { value: 'admin', label: '管理员' },
    ]),
  },
})
</script>

<template>
  <FormField v-model.trim="form.username" label="用户名" :minlength="3" :maxlength="50" :required="true" />
  <FormField
    v-model.trim="form.organization"
    label="所属单位"
    placeholder="例如：XX 环卫中心"
    :maxlength="120"
    :required="true"
  />
  <FormField
    v-model="form.password"
    :label="passwordLabel"
    type="password"
    :minlength="6"
    :required="requirePassword"
    :placeholder="passwordPlaceholder"
  />
  <FormField
    v-model="form.confirm_password"
    :label="confirmPasswordLabel"
    type="password"
    :minlength="6"
    :required="requirePassword"
    :placeholder="confirmPasswordPlaceholder"
  />
  <FormField
    v-if="includeSecurityCode"
    v-model.trim="form.security_code"
    label="安全码"
    :maxlength="32"
    :required="true"
  />
  <FormField
    v-if="includeRole"
    v-model="form.role"
    label="角色"
    as="select"
    :required="true"
    :options="roleOptions"
  />
</template>
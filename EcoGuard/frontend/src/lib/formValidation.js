export function normalizeText(value) {
  return String(value ?? '').trim()
}

export function validateTextField(value, label, options = {}) {
  const {
    min = 0,
    max = Number.POSITIVE_INFINITY,
    required = true,
  } = options

  const text = normalizeText(value)
  if (!text) {
    return required ? `${label}不能为空` : null
  }
  if (text.length < min) {
    return `${label}长度不能少于 ${min} 个字符`
  }
  if (text.length > max) {
    return `${label}长度不能超过 ${max} 个字符`
  }
  return null
}

export function validatePasswordPair(password, confirmPassword, options = {}) {
  const {
    label = '密码',
    min = 6,
    max = 128,
    required = true,
    allowEmpty = false,
  } = options

  const passwordText = normalizeText(password)
  const confirmText = normalizeText(confirmPassword)

  if (!passwordText && !confirmText) {
    return allowEmpty || !required ? null : `${label}不能为空`
  }
  if (!passwordText || !confirmText) {
    return `请确认${label}`
  }
  if (passwordText.length < min) {
    return `${label}长度不能少于 ${min} 位`
  }
  if (passwordText.length > max) {
    return `${label}长度不能超过 ${max} 位`
  }
  if (passwordText !== confirmText) {
    return `两次输入的${label}不一致`
  }
  return null
}

export function validateSelection(value, label, allowedValues) {
  const normalized = normalizeText(value)
  if (!allowedValues.includes(normalized)) {
    return `${label}仅支持 ${allowedValues.join(' 或 ')}`
  }
  return null
}

export function firstValidationError(...messages) {
  return messages.find((message) => Boolean(message)) || null
}

export function validateUserProfileForm(form, options = {}) {
  const {
    requireSecurityCode = false,
    requirePassword = false,
    allowEmptyPassword = false,
    requireRole = false,
  } = options

  return firstValidationError(
    validateTextField(form?.username, '用户名', { min: 3, max: 50 }),
    validateTextField(form?.organization, '所属单位', { min: 1, max: 120 }),
    requireSecurityCode ? validateTextField(form?.security_code, '安全码', { min: 4, max: 32 }) : null,
    validatePasswordPair(form?.password, form?.confirm_password, {
      label: '密码',
      min: 6,
      max: 128,
      required: requirePassword,
      allowEmpty: allowEmptyPassword,
    }),
    requireRole ? validateSelection(form?.role, '角色', ['user', 'admin']) : null,
  )
}
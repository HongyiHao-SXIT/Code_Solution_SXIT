<script setup>
import { computed } from 'vue'

const props = defineProps({
  id: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  modelValue: {
    type: [String, Number, Boolean],
    default: '',
  },
  modelModifiers: {
    type: Object,
    default: () => ({}),
  },
  as: {
    type: String,
    default: 'input',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  minlength: {
    type: [String, Number],
    default: undefined,
  },
  maxlength: {
    type: [String, Number],
    default: undefined,
  },
  rows: {
    type: [String, Number],
    default: 3,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  trim: {
    type: Boolean,
    default: false,
  },
  options: {
    type: Array,
    default: () => [],
  },
  wrapperClass: {
    type: [String, Array, Object],
    default: '',
  },
  controlClass: {
    type: [String, Array, Object],
    default: '',
  },
  min: {
    type: [String, Number],
    default: undefined,
  },
  max: {
    type: [String, Number],
    default: undefined,
  },
  step: {
    type: [String, Number],
    default: undefined,
  },
})

const emit = defineEmits(['update:modelValue'])

const controlId = computed(() => props.id || `field-${props.label}`)
const controlValue = computed(() => (props.modelValue ?? ''))

function normalizeValue(value) {
  if (props.modelModifiers.number) {
    if (value === '' || value == null) {
      return ''
    }
    return Number(value)
  }
  if ((props.trim || props.modelModifiers.trim) && typeof value === 'string') {
    return value.trim()
  }
  return value
}

function updateValue(event) {
  emit('update:modelValue', normalizeValue(event.target.value))
}

function updateCheckbox(event) {
  emit('update:modelValue', Boolean(event.target.checked))
}

function optionValue(option) {
  if (option && typeof option === 'object') {
    return option.value
  }
  return option
}

function optionLabel(option) {
  if (option && typeof option === 'object') {
    return option.label ?? option.value
  }
  return option
}
</script>

<template>
  <label :class="['field-block', wrapperClass]" :for="controlId">
    <span v-if="label">{{ label }}</span>

    <textarea
      v-if="as === 'textarea'"
      :class="controlClass"
      :id="controlId"
      :value="controlValue"
      :placeholder="placeholder"
      :required="required"
      :minlength="minlength"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      :rows="rows"
      :disabled="disabled"
      @input="updateValue"
    />

    <select
      v-else-if="as === 'select'"
      :class="controlClass"
      :id="controlId"
      :value="controlValue"
      :required="required"
      :disabled="disabled"
      @change="updateValue"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option v-for="option in options" :key="optionValue(option)" :value="optionValue(option)">
        {{ optionLabel(option) }}
      </option>
    </select>

    <input
      v-else-if="type === 'checkbox'"
      :class="controlClass"
      :id="controlId"
      :checked="Boolean(modelValue)"
      :disabled="disabled"
      :required="required"
      type="checkbox"
      @change="updateCheckbox"
    >

    <input
      v-else
      :class="controlClass"
      :id="controlId"
      :value="controlValue"
      :type="type"
      :placeholder="placeholder"
      :required="required"
      :minlength="minlength"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      @input="updateValue"
    >
  </label>
</template>
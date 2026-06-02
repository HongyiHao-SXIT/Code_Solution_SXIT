import { ref } from 'vue'
import { postJson } from '../lib/api'
import { pushFlash } from '../stores/session'

export function useAuthSubmission(options) {
  const loading = ref(false)
  const {
    endpoint,
    successMessage,
    errorMessage,
    onSuccess,
    onError,
  } = options

  async function submit(payload) {
    loading.value = true
    try {
      const response = await postJson(endpoint, payload)
      pushFlash(response.message || successMessage, 'success')
      if (onSuccess) {
        await onSuccess(response)
      }
      return response
    } catch (error) {
      pushFlash(error.message || errorMessage, 'error')
      if (onError) {
        await onError(error)
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    submit,
  }
}
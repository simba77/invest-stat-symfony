import type {Ref} from 'vue'
import {ref} from 'vue'

interface UseAsync<T extends (...args: any[]) => unknown> {
  loading: Ref<boolean>
  validationErrors: Ref<any>
  run: (...args: Parameters<T>) => Promise<ReturnType<T> | unknown>
}

export default function useAsync<T extends (...args: any[]) => unknown>(fn: T): UseAsync<T> {
  const loading: UseAsync<T>['loading'] = ref(false)
  const validationErrors: UseAsync<T>['validationErrors'] = ref()

  const run: UseAsync<T>['run'] = async (...args) => {
    loading.value = true
    try {
      const result = await fn(...args)
      return result as ReturnType<T>
    } catch (error: any) {
      if (error?.response?.status === 422) {
        validationErrors.value = error.response.data
      } else {
        console.log(error)
        alert('An Error has Occurred')
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  return {loading, validationErrors, run}
}

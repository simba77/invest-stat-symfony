import type {Ref} from 'vue'
import {ref} from 'vue'

interface UseAsync<T extends (...args: unknown[]) => unknown> {
  loading: Ref<boolean>
  run: (...args: Parameters<T>) => Promise<ReturnType<T>>
}

export default function useAsync<T extends (...args: unknown[]) => unknown>(fn: T): UseAsync<T> {
  const loading: UseAsync<T>['loading'] = ref(false)

  const run: UseAsync<T>['run'] = async (...args) => {
    loading.value = true
    try {
      const result = await fn(...args)
      return result as ReturnType<T>
    } catch (error) {
      console.log(error);
      alert('An Error has Occured');
      throw error
    } finally {
      loading.value = false
    }
  }

  return {loading, run}
}

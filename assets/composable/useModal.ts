import {markRaw, reactive, ref, shallowRef} from 'vue'

interface ModalConfig {
  component: object
  title?: string
  modelValue?: any
  classes?: string
  verticalCentered?: boolean
  withTopMargin?: boolean // Добавляет отступ вверху для мобилок. Полезно на небольших модальных окнах
}

interface ModalParams {
  classes: string
  verticalCentered: boolean
  isOpen: boolean
  modelValue: any
}

const view = shallowRef<any>({})
const modalInstance = ref<any>(null)

const params = reactive<ModalParams>({
  classes: '',
  verticalCentered: false,
  isOpen: false,
  modelValue: {}
})

function init (modal: any) {
  modalInstance.value = modal
}

function open (config: ModalConfig) {
  modalInstance.value.show()
  view.value = markRaw(config.component)

  // Дополнительные параметры
  params.isOpen = true
  params.classes = config?.classes ?? ''
  params.verticalCentered = config?.verticalCentered ?? true
  params.modelValue = config?.modelValue ?? {}
}

function close () {
  modalInstance.value.hide()
}

export const useModal = () => {
  return {
    view,
    params,
    close,
    open,
    init
  }
}

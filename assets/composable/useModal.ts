import {markRaw, ref, shallowRef} from "vue";

interface ModalConfig {
  component: object
  modelValue?: any
}

const isOpen = ref<boolean>(false);
const view = shallowRef<any>({});
const model = ref<object>({});

function open(config: ModalConfig) {
  isOpen.value = true;
  model.value = config.modelValue ?? {};
  view.value = markRaw(config.component);
}

function close() {
  isOpen.value = false;
}

export const useModal = () => {
  return {
    isOpen,
    view,
    model,
    close,
    open
  }
}

import {markRaw, ref, shallowRef} from "vue";

export type ModalAction = {
  label: string,
  classes: string[],
  callback: (props?: any) => void,
};

const isOpen = ref<boolean>(false);
const view = shallowRef<any>({});
const model = ref<object>({});
const actions = ref<ModalAction[]>([]);

function open(component: object, modelValue?: any, modalActions?: ModalAction[]) {
  isOpen.value = true;
  model.value = modelValue ?? {};
  actions.value = modalActions ?? [];
  view.value = markRaw(component);
}

function close() {
  isOpen.value = false;
}

export function useModal() {
  return {
    isOpen,
    view,
    model,
    actions,
    close,
    open
  }
}

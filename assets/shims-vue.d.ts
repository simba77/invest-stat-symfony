declare module "*.vue" {
  import type { DefineComponent } from "vue";
  const component: DefineComponent<object, object, any>;
  export default component;
}

declare module 'lodash';

import 'vue'

declare module 'vue' {
  interface GlobalDirectives {
    tooltip: any
  }
}

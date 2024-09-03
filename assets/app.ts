import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import {createApp} from 'vue';
import {createPinia} from 'pinia';
import router from './router';
import AppComponent from "./App.vue";
import {authStore} from "./stores/authStore";
import FloatingVue from 'floating-vue';
import './scss/app.scss';
import 'floating-vue/dist/style.css';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';

const app = createApp(AppComponent)
app.use(createPinia())

app.use(FloatingVue)

app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      cssLayer: {
        name: 'primevue',
        order: 'tailwind-base, primevue, tailwind-utilities',
      },
      darkModeSelector: '.app-dark',
    },
  }
})

// Check auth
router.beforeEach((to) => {
  const auth = authStore();
  if (to.meta.requiresAuth && !auth.userData) {
    return '/login'
  }

  // Redirect authorized users from guest pages
  if (to.meta.onlyGuests && auth.userData) {
    return '/'
  }
})

const auth = authStore();

// Checking auth and mount app
auth.checkAuth()
  .finally(() => {
    app.use(router)
    app.mount('#app')
  });

<script setup lang="ts">
import {Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import {Bars3Icon, XMarkIcon, UserIcon, SunIcon, MoonIcon} from '@heroicons/vue/24/outline'
import {authStore} from "@/stores/authStore";
import {useRoute} from "vue-router";
import Button from 'primevue/button';
import {useTemplate} from "@/composable/useTemplate";
const {toggleTheme, currentTheme} = useTemplate()

const route = useRoute()

const props = defineProps({
  title: {
    type: String,
    default: null,
  }
})

const user = authStore().userData;

const navigation = [
  {
    name: 'Portfolio',
    routeName: 'Portfolio',
    current: route.name === 'Portfolio'
  },
  {
    name: 'Accounts',
    routeName: 'Accounts',
    current: route.name === 'Accounts'
  },
  {
    name: 'Closed Deals',
    routeName: 'ClosedDeals',
    current: route.name === 'ClosedDeals'
  },
  {
    name: 'Expenses',
    routeName: 'Expenses',
    current: route.name === 'Expenses'
  },
  {
    name: 'Deposits',
    routeName: 'Deposits',
    current: route.name === 'Deposits'
  },
  {
    name: 'Investments',
    routeName: 'Investments',
    current: route.name === 'Investments'
  },
  {
    name: 'Dividends',
    routeName: 'Dividends',
    current: route.name === 'Dividends'
  },
  {
    name: 'Coupons',
    routeName: 'Coupons',
    current: route.name === 'Coupons'
  },
  {
    name: 'Future Multipliers',
    routeName: 'FuturesMultipliers',
    current: route.name === 'FuturesMultipliers'
  }
]
const userNavigation = [
  {name: 'Change Profile', href: '/change-profile'},
  {name: 'Logout', href: '/api/logout'},
]
</script>
<template>
  <div class="min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
      <div class="container">
        <!-- Logo -->
        <router-link class="navbar-brand d-flex align-items-center" to="/">
          <img src="../images/workflow-mark-indigo-300.svg" alt="Logo" width="32" height="32" />
        </router-link>

        <!-- Burger -->
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#mainNavbar"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Content -->
        <div class="collapse navbar-collapse" id="mainNavbar">
          <!-- Left menu -->
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li
              v-for="item in navigation"
              :key="item.routeName"
              class="nav-item"
            >
              <router-link
                class="nav-link"
                :class="{ active: route.name === item.routeName }"
                :to="{ name: item.routeName }"
              >
                {{ item.name }}
              </router-link>
            </li>
          </ul>

          <!-- Right controls -->
          <div class="d-flex align-items-center gap-3">
            <!-- Theme switch -->
            <Button
              rounded
              size="small"
              text
              class="border-0"
              @click="toggleTheme()"
            >
              <SunIcon v-if="currentTheme === 'light'" class="h-5 w-5" />
              <MoonIcon v-else class="h-5 w-5" />
            </Button>

            <!-- User dropdown -->
            <div class="dropdown">
              <button
                class="btn btn-link dropdown-toggle text-white d-flex align-items-center gap-2"
                data-bs-toggle="dropdown"
              >
                <span
                  class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                  style="width: 36px; height: 36px"
                >
                  <UserIcon class="h-5 w-5 text-dark" />
                </span>
                <strong>{{ user.name }}</strong>
              </button>

              <ul class="dropdown-menu dropdown-menu-end">
                <li
                  v-for="item in userNavigation"
                  :key="item.name"
                >
                  <a class="dropdown-item" :href="item.href">
                    {{ item.name }}
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page title -->
    <header v-if="title" class="shadow-sm dark:bg-surface-900">
      <div class="container py-3">
        <h1 class="mb-0">{{ title }}</h1>
      </div>
    </header>

    <main class="container py-3">
      <slot />
    </main>
  </div>
</template>


<style scoped lang="scss">
.switch-theme-btn {

}
</style>

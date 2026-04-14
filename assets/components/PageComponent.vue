<script setup lang="ts">
import { Sun, Moon, User } from 'lucide-vue-next'
import {authStore} from "@/stores/authStore";
import {useRoute} from "vue-router";
import {useTemplate} from "@/composable/useTemplate";
const {toggleTheme, currentTheme} = useTemplate()

const route = useRoute()

defineProps({
  title: {
    type: String,
    default: null,
  }
})

const userData = authStore().userData;

interface NavItem {
  name: string
  routeName: string | null
  children?: { name: string; routeName: string }[]
}

const analyticsRoutes = ['ClosedDeals', 'DepositStats']

const navigation: NavItem[] = [
  {
    name: 'Portfolio',
    routeName: 'Portfolio',
  },
  {
    name: 'Accounts',
    routeName: 'Accounts',
  },
  {
    name: 'Analytics',
    routeName: null,
    children: [
      { name: 'Closed Deals', routeName: 'ClosedDeals' },
      { name: 'Deposit Stats', routeName: 'DepositStats' },
    ],
  },
  {
    name: 'Expenses',
    routeName: 'Expenses',
  },
  {
    name: 'Deposits',
    routeName: 'Deposits',
  },
  {
    name: 'Investments',
    routeName: 'Investments',
  },
  {
    name: 'Dividends',
    routeName: 'Dividends',
  },
  {
    name: 'Coupons',
    routeName: 'Coupons',
  },
  {
    name: 'Future Multipliers',
    routeName: 'FuturesMultipliers',
  },
]
const userNavigation = [
  {name: 'Change Profile', href: '/change-profile'},
  {name: 'Logout', href: '/api/logout', external: true},
]
</script>
<template>
  <div class="min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
      <div class="container">
        <!-- Logo -->
        <router-link class="navbar-brand d-flex align-items-center" to="/">
          <img src="../images/workflow-mark-indigo-300.svg" alt="Logo" width="32" height="32">
        </router-link>

        <!-- Burger -->
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#mainNavbar"
        >
          <span class="navbar-toggler-icon" />
        </button>

        <!-- Content -->
        <div id="mainNavbar" class="collapse navbar-collapse">
          <!-- Left menu -->
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <template
              v-for="item in navigation"
              :key="item.routeName ?? item.name"
            >
              <!-- Dropdown item -->
              <li v-if="item.children" class="nav-item dropdown">
                <a
                  class="nav-link dropdown-toggle"
                  :class="{ active: analyticsRoutes.includes(String(route.name)) }"
                  href="#"
                  role="button"
                  data-bs-toggle="dropdown"
                >
                  {{ item.name }}
                </a>
                <ul class="dropdown-menu">
                  <li
                    v-for="child in item.children"
                    :key="child.routeName"
                  >
                    <router-link
                      class="dropdown-item"
                      :class="{ active: route.name === child.routeName }"
                      :to="{ name: child.routeName }"
                    >
                      {{ child.name }}
                    </router-link>
                  </li>
                </ul>
              </li>
              <!-- Regular item -->
              <li v-else class="nav-item">
                <router-link
                  class="nav-link"
                  :class="{ active: route.name === item.routeName }"
                  :to="{ name: item.routeName! }"
                >
                  {{ item.name }}
                </router-link>
              </li>
            </template>
          </ul>

          <!-- Right controls -->
          <div class="d-flex align-items-center gap-3">
            <!-- Theme switch -->
            <button
              type="button"
              class="btn btn-link text-white border-0 p-0"
              @click="toggleTheme()"
            >
              <Sun
                v-if="currentTheme === 'light'"
                :size="20"
              />
              <Moon
                v-else
                :size="20"
              />
            </button>

            <!-- User dropdown -->
            <div class="dropdown">
              <button
                class="btn btn-link dropdown-toggle text-white d-flex align-items-center gap-2"
                data-bs-toggle="dropdown"
              >
                <span
                  class="bg-light rounded-circle d-flex align-items-center justify-content-center text-dark"
                  style="width: 36px; height: 36px"
                >
                  <User :size="18" />
                </span>
                <strong>{{ userData?.name }}</strong>
              </button>

              <ul class="dropdown-menu dropdown-menu-end">
                <li
                  v-for="item in userNavigation"
                  :key="item.name"
                >
                  <a v-if="item?.external" class="dropdown-item" :href="item.href">{{ item.name }}</a>
                  <router-link v-else class="dropdown-item" :to="item.href">
                    {{ item.name }}
                  </router-link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page title -->
    <header v-if="title" class="shadow-sm page-header">
      <div class="container py-3">
        <h1 class="mb-0">
          {{ title }}
        </h1>
      </div>
    </header>

    <main class="container py-3">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import {Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import {Bars3Icon, XMarkIcon, UserIcon} from '@heroicons/vue/24/outline'
import {authStore} from "@/stores/authStore";
import {useRoute} from "vue-router";

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
  }
]
const userNavigation = [
  {name: 'Change Profile', href: '/change-profile'},
  {name: 'Logout', href: '/api/logout'},
]
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-transparent">
    <Disclosure
      v-slot="{ open }"
      as="nav"
      class="bg-indigo-600 shadow"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <router-link to="/">
                <img
                  class="h-8 w-8"
                  src="../images/workflow-mark-indigo-300.svg"
                  alt="Workflow"
                >
              </router-link>
            </div>
            <div class="hidden md:block">
              <div class="ml-10 flex items-baseline space-x-4">
                <router-link
                  v-for="item in navigation"
                  :key="item.name"
                  :to="{name: item.routeName}"
                  :class="[
                    item.current ? 'bg-indigo-700 text-white' : 'text-white hover:bg-indigo-800 hover:text-white',
                    'px-3 py-2 rounded-md text-sm font-medium'
                  ]"
                  :aria-current="item.current ? 'page' : undefined"
                >
                  {{ item.name }}
                </router-link>
              </div>
            </div>
          </div>
          <div class="hidden md:block">
            <div class="ml-4 flex items-center md:ml-6">
              <!-- Profile dropdown -->
              <Menu
                as="div"
                class="ml-3 relative"
              >
                <MenuButton class="max-w-xs flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:rounded-full">
                  <span class="sr-only">Open user menu</span>
                  <div class="bg-indigo-100 ring-2 ring-indigo-400 h-10 w-10 rounded-full shadow flex items-center justify-center font-extrabold text-xl">
                    <user-icon class="text-gray-500 h-6 w-6" />
                  </div>
                  <div class="text-white ml-4 mr-4 font-bold">
                    {{ user.name }}
                  </div>
                </MenuButton>
                <transition
                  enter-active-class="transition ease-out duration-100"
                  enter-from-class="transform opacity-0 scale-95"
                  enter-to-class="transform opacity-100 scale-100"
                  leave-active-class="transition ease-in duration-75"
                  leave-from-class="transform opacity-100 scale-100"
                  leave-to-class="transform opacity-0 scale-95"
                >
                  <MenuItems class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <MenuItem
                      v-for="item in userNavigation"
                      :key="item.name"
                      v-slot="{ active }"
                    >
                      <a
                        :href="item.href"
                        :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                      >{{ item.name }}</a>
                    </MenuItem>
                  </MenuItems>
                </transition>
              </Menu>
            </div>
          </div>
          <div class="-mr-2 flex md:hidden">
            <!-- Mobile menu button -->
            <DisclosureButton
              class="btn btn-primary border-indigo-700"
            >
              <span class="sr-only">Open main menu</span>
              <Bars3Icon
                v-if="!open"
                class="block h-6 w-6"
                aria-hidden="true"
              />
              <XMarkIcon
                v-else
                class="block h-6 w-6"
                aria-hidden="true"
              />
            </DisclosureButton>
          </div>
        </div>
      </div>

      <DisclosurePanel class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="{name: item.routeName}"
            :class="[item.current ? 'bg-indigo-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white', 'block px-3 py-2 rounded-md text-base font-medium']"
            :aria-current="item.current ? 'page' : undefined"
          >
            {{ item.name }}
          </router-link>
        </div>
        <div class="pt-4 pb-3 border-t border-gray-700">
          <div class="flex items-center px-5">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-full text-white shadow flex items-center justify-center font-extrabold text-xl">
                <b>A</b>
              </div>
            </div>
            <div class="ml-3">
              <div class="text-base font-medium leading-none text-white">
                {{ user.name }}
              </div>
              <div class="text-sm font-medium leading-none text-gray-400">
                {{ user.email }}
              </div>
            </div>
          </div>
          <div class="mt-3 px-2 space-y-1">
            <DisclosureButton
              v-for="item in userNavigation"
              :key="item.name"
              as="a"
              :href="item.href"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-gray-700"
            >
              {{ item.name }}
            </DisclosureButton>
          </div>
        </div>
      </DisclosurePanel>
    </Disclosure>

    <header
      v-if="title"
      class="shadow bg-white dark:bg-surface-900"
    >
      <div class="max-w-7xl mx-auto py-3.5 px-4 sm:px-6 lg:px-8">
        <h1 class="text-xl font-bold">
          {{ props.title }}
        </h1>
      </div>
    </header>
    <main>
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Replace with your content -->
        <div class="px-4 py-4 sm:px-0">
          <slot />
        </div>
        <!-- /End replace -->
      </div>
    </main>
  </div>
</template>

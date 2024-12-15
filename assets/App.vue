<script setup lang="ts">
import BaseModal from "@/components/Modals/BaseModal.vue";
import {useTemplate} from "@/composable/useTemplate";
import {computed, onMounted} from "vue";
import { useTitle } from '@vueuse/core'
import {usePage} from "@/composable/usePage";

const {pageTitle} = usePage()
const {setCurrentTheme} = useTemplate()

onMounted(() => {
  setCurrentTheme()
})

const title = computed(() => {
  return !pageTitle.value ? 'Invest Stat' : `${pageTitle.value} | Invest Stat`
})

useTitle(title)

</script>

<template>
  <div>
    <router-view />
    <base-modal />
  </div>
</template>

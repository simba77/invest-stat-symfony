<script setup lang="ts">
import {useTemplate} from "@/composable/useTemplate";
import {computed, onMounted} from "vue";
import { useTitle } from '@vueuse/core'
import {usePage} from "@/composable/usePage";
import ModalComponent from "@/components/Modals/ModalComponent.vue";

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
    <modal-component />
  </div>
</template>

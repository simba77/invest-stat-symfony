<script setup lang="ts">
import BaseModal from "@/components/Modals/BaseModal.vue";
import {useTemplate} from "@/composable/useTemplate";
import {onMounted} from "vue";

const {setCurrentTheme} = useTemplate()

onMounted(() => {
  setCurrentTheme()
})

</script>

<template>
  <div>
    <router-view />
    <base-modal />
  </div>
</template>

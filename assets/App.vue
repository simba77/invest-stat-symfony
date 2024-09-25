<script setup lang="ts">
import BaseModal from "@/components/Modals/BaseModal.vue";
import {useTemplate} from "@/composable/useTemplate";

const {currentTheme} = useTemplate()
</script>

<template>
  <div :class="{'app-dark': currentTheme === 'dark'}">
    <router-view />
    <base-modal />
  </div>
</template>

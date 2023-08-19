<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import {useSavings} from '@/composable/useSavings'
import FormComponent from '@/components/Forms/FormComponent.vue'
import {useRoute, useRouter} from 'vue-router'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'

const router = useRouter()
const route = useRoute()

const saving = useSavings()
saving.loadForm(route.params.id ?? 0)

function submitForm() {
  saving.create(saving.form.value, () => {
    router.push({'name': 'Savings'})
  })
}
</script>

<template>
  <page-component title="Add Deposit">
    <div class="card">
      <form class="space-y-6 w-full md:w-2/3 mx-auto" @submit.prevent="submitForm">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Deposit</h3>
        </div>
        <preloader-component v-if="saving.loadingForm.value"></preloader-component>
        <div v-else class="w-full md:w-2/4">
          <form-component v-model="saving.form.value" :errors="saving.formErrors.value"/>
        </div>
        <div class="border-b"></div>
        <button type="submit" class="btn btn-primary" :disabled="saving.creating.value">Save</button>
        <router-link :to="{name: 'Savings'}" class="btn btn-secondary ml-3">Back</router-link>
      </form>
    </div>
  </page-component>
</template>

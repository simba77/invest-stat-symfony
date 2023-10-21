<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import {useDepositAccounts} from '@/composable/useDepositAccounts'
import FormComponent from '@/components/Forms/FormComponent.vue'
import {useRoute, useRouter} from 'vue-router'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'

const router = useRouter()
const route = useRoute()

const savingAccounts = useDepositAccounts()
savingAccounts.loadForm(route.params.id ?? 0)

function submitForm() {
  savingAccounts.create(savingAccounts.form.value, () => {
    router.push({'name': 'SavingAccounts'})
  })
}
</script>

<template>
  <page-component title="Add Saving Account">
    <div class="card">
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            Saving Account
          </h3>
        </div>
        <preloader-component v-if="savingAccounts.loadingForm.value" />
        <div
          v-else
          class="w-full md:w-2/4"
        >
          <form-component
            v-model="savingAccounts.form.value"
            :errors="savingAccounts.formErrors.value"
          />
        </div>
        <div class="border-b" />
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="savingAccounts.creating.value"
        >
          Save
        </button>
        <router-link
          :to="{name: 'SavingAccounts'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

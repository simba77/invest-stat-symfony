<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import {useDepositAccounts} from '@/composable/useDepositAccounts'
import {useRoute, useRouter} from 'vue-router'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import InputText from "@/components/Forms/InputText.vue";
import {reactive, ref} from "vue";
import axios from "axios";
import useAsync from "@/utils/use-async";

const router = useRouter()
const route = useRoute()
const componentKey = ref(0);
const savingAccounts = useDepositAccounts()

if (route.params.id) {
  savingAccounts.loadForm(route.params.id)
}

const form = reactive({
  formData: {
    name: ''
  },
  errors: undefined
})

const {loading, run: submitForm} = useAsync(async () => {
  await axios.post('/api/deposits/accounts/create/', form.formData)
    .then(() => {
      router.push({'name': 'DepositAccounts'})
    })
    .catch((e) => {
      if (e?.response?.status === 422) {
        form.errors = e.response.data.errors;
      } else {
        throw e;
      }
    })
})

</script>

<template>
  <page-component title="Add Deposit Account">
    <div class="card">
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            Deposit Account
          </h3>
        </div>
        <preloader-component v-if="savingAccounts.loadingForm.value" />
        <div
          v-else
          class="w-full md:w-2/4"
        >
          <input-text
            :key="componentKey"
            v-model="form.formData.name"
            :error="form.errors"
            class="mb-3"
            name="name"
            label="Account Name"
            placeholder="Enter an Account Name"
          />
        </div>
        <div class="border-b" />
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="loading"
        >
          Save
        </button>
        <router-link
          :to="{name: 'DepositAccounts'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

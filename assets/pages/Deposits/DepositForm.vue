<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import {useRoute, useRouter} from 'vue-router'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import {reactive, ref} from "vue";
import InputText from "@/components/Forms/InputText.vue";
import InputSelect from "@/components/Forms/InputSelect.vue";
import {useDepositAccounts} from "@/composable/useDepositAccounts";
import useAsync from "@/utils/use-async";
import axios from "axios";

const router = useRouter()
const route = useRoute()
const componentKey = ref(0);
const form = reactive({
  formData: {
    accountId: '',
    sum: '',
    type: 1,
    date: ''
  },
  errors: undefined
})

const {accounts, getAccounts} = useDepositAccounts()

getAccounts()


const {loading, run: submitForm, validationErrors} = useAsync(async () => {
  const url = route.params.id ? '/api/deposits/update/' + route.params.id : '/api/deposits/create';
  await axios.post(url, form.formData)
    .then(() => {
      router.push({'name': 'Deposits'})
    })
})

const {loading: loadingForm, run: getFormData} = useAsync(async () => {
  await axios.get('/api/deposits/get-form/' + route.params.id)
    .then((response) => {
      form.formData.accountId = response.data.accountId
      form.formData.sum = response.data.sum
      form.formData.type = response.data.type
      form.formData.date = response.data.date
    })
})

if (route.params.id) {
  getFormData()
}

</script>

<template>
  <page-component title="Add Deposit">
    <div class="card">
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            Deposit
          </h3>
        </div>
        <preloader-component v-if="loadingForm" />
        <div
          v-else
          class="w-full md:w-2/4"
        >
          <input-select
            :key="componentKey"
            v-model.number="form.formData.accountId"
            class="mb-3"
            label="Account"
            name="accountId"
            placeholder="Select Account"
            field-value="id"
            :error="validationErrors"
            :options="accounts?.items"
          />

          <input-text
            :key="componentKey"
            v-model.trim="form.formData.sum"
            :error="validationErrors"
            class="mb-3"
            name="sum"
            label="Sum"
            placeholder="Sum"
          />

          <input-select
            :key="componentKey"
            v-model.number="form.formData.type"
            class="mb-3"
            label="Type"
            name="type"
            placeholder="Select Type"
            field-value="id"
            :error="validationErrors"
            :options="[{id: 1, name: 'Deposit'}, {id: 2, name: 'Percent'}]"
          />

          <input-text
            :key="componentKey"
            v-model="form.formData.date"
            :error="validationErrors"
            type="date"
            class="mb-3"
            name="date"
            label="Date"
            placeholder="Date"
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
          :to="{name: 'Deposits'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

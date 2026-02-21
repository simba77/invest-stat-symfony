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
      <div class="card-body py-4">
        <form @submit.prevent="submitForm">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-8">
              <div class="mb-3">
                <h3 class="fw-bold">
                  Deposit
                </h3>
              </div>
              <preloader-component v-if="loadingForm" />
              <div
                v-else
                class="form-stack"
              >
                <input-select
                  :key="componentKey"
                  v-model.number="form.formData.accountId"
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
                  name="sum"
                  label="Sum"
                  placeholder="Sum"
                  type="number"
                />

                <input-select
                  :key="componentKey"
                  v-model.number="form.formData.type"
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
                  name="date"
                  label="Date"
                  placeholder="Date"
                />
              </div>
              <hr class="my-4">
              <div>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="loading"
                >
                  Save
                </button>
                <router-link
                  :to="{name: 'Deposits'}"
                  class="btn btn-secondary ms-2"
                >
                  Back
                </router-link>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </page-component>
</template>

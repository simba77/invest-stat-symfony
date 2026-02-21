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

const form = reactive({
  formData: {
    name: ''
  },
  errors: undefined
})

const {loading, run: submitForm, validationErrors} = useAsync(async () => {
  const url = route.params.id ? '/api/deposits/accounts/update/' + route.params.id : '/api/deposits/accounts/create';
  await axios.post(url, form.formData)
    .then(() => {
      router.push({'name': 'DepositAccounts'})
    })
})

const {loading: loadingForm, run: getFormData} = useAsync(async () => {
  await axios.get('/api/deposits/accounts/get-form/' + route.params.id)
    .then((response) => {
      form.formData.name = response.data.name
    })
})

if (route.params.id) {
  getFormData()
}

</script>

<template>
  <page-component :title="route.params.id ? 'Edit Deposit Account' : 'Add Deposit Account'">
    <div class="card">
      <div class="card-body py-4">
        <form @submit.prevent="submitForm">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-8">
              <div class="mb-3">
                <h3 class="fw-bold">
                  Deposit Account
                </h3>
              </div>
              <preloader-component v-if="savingAccounts.loadingForm.value || loadingForm" />
              <div
                v-else
                class="form-stack"
              >
                <input-text
                  :key="componentKey"
                  v-model="form.formData.name"
                  :error="validationErrors"
                  name="name"
                  label="Account Name"
                  placeholder="Enter an Account Name"
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
                  :to="{name: 'DepositAccounts'}"
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

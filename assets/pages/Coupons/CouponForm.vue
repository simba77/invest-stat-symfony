<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import {useRoute, useRouter} from 'vue-router'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import {reactive, ref} from "vue";
import InputText from "@/components/Forms/InputText.vue";
import InputSelect from "@/components/Forms/InputSelect.vue";
import useAsync from "@/utils/use-async";
import axios from "axios";
import useAccounts from "@/composable/useAccounts";

const router = useRouter()
const route = useRoute()
const componentKey = ref(0);
const form = reactive({
  fields: {
    accountId: '',
    ticker: '',
    stockMarket: 'MOEX',
    amount: '',
    date: ''
  },
  errors: undefined
})

const {accounts, getAccounts} = useAccounts()

getAccounts()


const {loading, run: submitForm, validationErrors} = useAsync(async () => {
  const url = route.params.id ? '/api/coupons/update/' + route.params.id : '/api/coupons/create';
  await axios.post(url, form.fields)
    .then(() => {
      router.push({'name': 'Coupons'})
    })
})

const {loading: loadingForm, run: getFormData} = useAsync(async () => {
  await axios.get('/api/coupons/get-form/' + route.params.id)
    .then((response) => {
      form.fields.accountId = response.data.accountId
      form.fields.ticker = response.data.ticker
      form.fields.stockMarket = response.data.stockMarket
      form.fields.amount = response.data.amount
      form.fields.date = response.data.date
    })
})

if (route.params.id) {
  getFormData()
}

</script>

<template>
  <page-component title="Add Coupon">
    <div class="card">
      <div class="card-body py-4">
        <form @submit.prevent="submitForm">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-8">
              <div class="mb-4">
                <div class="form-title">
                  Coupon
                </div>
              </div>
              <preloader-component v-if="loadingForm" />
              <div
                v-else
                class="form-stack"
              >
                <input-select
                  :key="componentKey"
                  v-model.number="form.fields.accountId"
                  label="Account"
                  name="accountId"
                  placeholder="Select Account"
                  field-value="id"
                  :error="validationErrors"
                  :options="accounts"
                />

                <input-text
                  :key="componentKey"
                  v-model.trim="form.fields.ticker"
                  :error="validationErrors"
                  name="ticker"
                  label="Ticker"
                  placeholder="Ticker"
                />

                <input-select
                  :key="componentKey"
                  v-model="form.fields.stockMarket"
                  label="Stock Market"
                  name="stockMarket"
                  placeholder="Stock Market"
                  :error="validationErrors"
                  :options="[{value: 'SPB', name: 'SPB'}, {value: 'MOEX', name: 'MOEX'}]"
                />

                <input-text
                  :key="componentKey"
                  v-model.trim="form.fields.amount"
                  :error="validationErrors"
                  name="amount"
                  label="Amount"
                  placeholder="Amount"
                  type="number"
                />

                <input-text
                  :key="componentKey"
                  v-model="form.fields.date"
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
                  <span
                    v-if="loading"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                  />
                  Save
                </button>
                <router-link
                  :to="{name: 'Coupons'}"
                  class="btn btn-secondary ms-3"
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

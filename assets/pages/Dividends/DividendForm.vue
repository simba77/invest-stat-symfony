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
  const url = route.params.id ? '/api/dividends/update/' + route.params.id : '/api/dividends/create';
  await axios.post(url, form.fields)
    .then(() => {
      router.push({'name': 'Dividends'})
    })
})

const {loading: loadingForm, run: getFormData} = useAsync(async () => {
  await axios.get('/api/dividends/get-form/' + route.params.id)
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
  <page-component title="Add Dividend">
    <div class="card">
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="form-title">
            Dividend
          </h3>
        </div>
        <preloader-component v-if="loadingForm" />
        <div
          v-else
          class="w-full md:w-2/4"
        >
          <input-select
            :key="componentKey"
            v-model.number="form.fields.accountId"
            class="mb-3"
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
            class="mb-3"
            name="ticker"
            label="Ticker"
            placeholder="Ticker"
          />

          <input-select
            :key="componentKey"
            v-model="form.fields.stockMarket"
            class="mb-3"
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
            class="mb-3"
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
            class="mb-3"
            name="date"
            label="Date"
            placeholder="Date"
          />
        </div>
        <div class="buttons-divider" />
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="loading"
        >
          Save
        </button>
        <router-link
          :to="{name: 'Dividends'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

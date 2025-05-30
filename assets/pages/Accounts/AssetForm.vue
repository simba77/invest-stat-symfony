<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import InputSelect from "@/components/Forms/InputSelect.vue";
import CheckboxComponent from "@/components/Forms/CheckboxComponent.vue";
import {onMounted, ref} from 'vue'
import useAsync from '@/utils/use-async'
import {useRoute} from 'vue-router'
import router from '@/router'
import {useDebounceFn, useFetch} from '@vueuse/core'
import Button from 'primevue/button';
import Divider from 'primevue/divider';

interface SecurityData {
  ticker: string
  shortName: string
  stockMarket: string
  price: number
  lotSize: number
}

const {params: routeParams} = useRoute()
const form = ref({
  ticker: '',
  stockMarket: 'MOEX',
  quantity: 1,
  buyPrice: '',
  isShort: false,
  targetPrice: '0',
})
const errors = ref(null)
const componentKey = ref(0)
const tickerComponentKey = ref(0)
const tickerData = ref<SecurityData | null>(null)


const {loading, run: submitForm} = useAsync(() => {
  const url = routeParams.id ? '/api/deals/edit/' + routeParams.id : '/api/deals/create/' + routeParams.account;
  axios.post(url, form.value)
    .then(() => {
      router.push({name: 'AccountDetail', params: {id: routeParams.account}});
    })
    .catch((error) => {
      if (error.response.status === 422 && error.response.data) {
        errors.value = error.response.data
        componentKey.value += 1
        tickerComponentKey.value += 1
      } else {
        throw error
      }
    })
})

const {run: getForm} = useAsync((id: any) => {
  axios.get('/api/deals/get-by-id/' + id)
    .then((response) => {
      form.value = response.data.deal
      componentKey.value += 1
      tickerComponentKey.value += 1
    })
})


const getTickerData = useDebounceFn(async () => {
  const {data} = await useFetch('/api/securities/get-data-by-ticker/' + form.value.ticker).json()
  if (data.value.security) {
    tickerData.value = data.value.security
    form.value.stockMarket = data.value.security.stockMarket;
    form.value.buyPrice = data.value.security.price;
    form.value.quantity = data.value.security.lotSize;
    // Добавляем 5% к текущей стоимости
    form.value.targetPrice = String(Math.round(parseFloat(data.value.security.price) + (parseFloat(data.value.security.price) * 0.05)));
    componentKey.value += 1
  }
}, 300)

onMounted(() => {
  if (routeParams.id) {
    getForm(routeParams.id);
  }
})

</script>

<template>
  <page-component title="Add Asset">
    <div class="card">
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        action="#"
        method="POST"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="form-title">
            Asset
          </h3>
          <p class="mt-1 text-sm text-gray-600">
            Enter the ticker and other params of asset
          </p>
        </div>
        <div class="w-full md:w-2/4">
          <checkbox-component
            :key="componentKey"
            v-model="form.isShort"
            label="Short"
            name="short"
          />

          <input-text
            :key="'ticker' + tickerComponentKey"
            v-model="form.ticker"
            :error="errors"
            class="mt-3"
            name="name"
            label="Ticker"
            placeholder="Enter a ticker"
            @update:model-value="getTickerData"
          />

          <div
            v-if="tickerData"
            class="mt-2"
          >
            {{ tickerData.shortName }}
          </div>

          <input-select
            :key="'stockMarket' + componentKey"
            v-model="form.stockMarket"
            :error="errors"
            :options="[{value: 'SPB', name: 'SPB'}, {value: 'MOEX', name: 'MOEX'}]"
            name="stockMarket"
            class="mt-3"
            label="Stock Market"
            placeholder="Stock Market"
          />
          <input-text
            :key="'quantity' + componentKey"
            v-model.number="form.quantity"
            :error="errors"
            type="number"
            class="mt-3"
            name="quantity"
            label="Quantity"
            placeholder="Quantity"
          />
          <input-text
            :key="'buyPrice' + componentKey"
            v-model.trim="form.buyPrice"
            :error="errors"
            class="mt-3"
            name="buyPrice"
            label="Buy Price"
            placeholder="Buy Price"
          />
          <input-text
            :key="'targetPrice' + componentKey"
            v-model.trim="form.targetPrice"
            :error="errors"
            class="mt-3"
            name="targetPrice"
            label="Target Price"
            placeholder="Target Price"
          />
        </div>
        <Divider />
        <Button
          type="submit"
          class="btn btn-primary"
          :loading="loading"
          label="Save"
        />
        <router-link
          :to="{name: 'AccountDetail', params: {id: $route.params.account}}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

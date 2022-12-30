<template>
  <page-component title="Add Asset">
    <div class="card">
      <form class="space-y-6 w-full md:w-2/3 mx-auto" action="#" method="POST" @submit.prevent="submitForm">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Asset</h3>
          <p class="mt-1 text-sm text-gray-600">Enter the ticker and other params of asset</p>
        </div>
        <div class="bg-red-500 inline-block text-white rounded px-4 py-2" v-if="errors && errors.message">{{ errors.message }}</div>
        <div class="w-full md:w-2/4">
          <checkbox-component
            label="Short"
            name="short"
            :key="componentKey"
            v-model="form.short"
          ></checkbox-component>
          <input-text
            v-model="form.ticker"
            :key="componentKey"
            :error="errors?.ticker"
            class="mt-3"
            name="name"
            label="Ticker"
            placeholder="Enter a ticker"
          />
          <input-select
            v-model="form.stock_market"
            :key="componentKey"
            :error="errors?.stock_market"
            :options="[{value: 'SPB', name: 'SPB'}, {value: 'MOEX', name: 'MOEX'}]"
            name="stock_market"
            class="mt-3"
            label="Stock Market"
          />
          <input-text
            v-model="form.quantity"
            :key="componentKey"
            :error="errors?.quantity"
            type="number"
            class="mt-3"
            name="quantity"
            label="Quantity"
            placeholder="Quantity"
          />
          <input-text
            v-model="form.buy_price"
            :key="componentKey"
            :error="errors?.buy_price"
            type="number"
            class="mt-3"
            name="buy_price"
            label="Buy Price"
            placeholder="Buy Price"
          />
          <input-text
            v-model="form.target_price"
            :key="componentKey"
            :error="errors?.target_price"
            type="number"
            class="mt-3"
            name="target_price"
            label="Target Price"
            placeholder="Target Price"
          />
          <input-select
            v-model="form.currency"
            :key="componentKey"
            :error="errors?.currency"
            :options="[{value: 'RUB', name: 'RUB'}, {value: 'USD', name: 'USD'}]"
            id="currency"
            class="mt-3"
            name="currency"
            label="Currency"
          />
        </div>
        <div class="border-b"></div>
        <button type="submit" class="btn btn-primary" :disabled="loading">Save</button>
        <router-link :to="{name: 'Accounts'}" class="btn btn-secondary ml-3">Back</router-link>
      </form>
    </div>
  </page-component>
</template>

<script lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import InputSelect from "@/components/Forms/InputSelect.vue";
import CheckboxComponent from "@/components/Forms/CheckboxComponent.vue";

export default {
  name: "AssetForm",
  components: {CheckboxComponent, InputSelect, InputText, PageComponent},
  data() {
    return {
      form: {
        ticker: '',
        stock_market: 'SPB',
        quantity: 1,
        buy_price: '',
        currency: 'USD',
        short: false,
      },
      loading: false,
      errors: null,
      componentKey: 0,
    }
  },
  mounted() {
    if (this.$route.params.id) {
      this.getForm(this.$route.params.id);
    }
  },
  methods: {
    submitForm() {
      this.loading = true;
      axios.post('/api/assets/store/' + this.$route.params.account, this.form)
        .then(() => {
          this.$router.push({name: 'Accounts'});
        })
        .catch((error) => {
          if (error.response.data.errors) {
            this.errors = error.response.data.errors;
            this.componentKey += 1;
          } else {
            alert('An error has occurred');
          }
        })
        .finally(() => {
          this.loading = false;
        })
    },
    getForm(id: number) {
      this.loading = true;
      axios.get('/api/assets/edit/' + id)
        .then((response) => {
          this.form = response.data.form;
          this.componentKey += 1;
        })
        .catch(() => {
          alert('An error has occurred');
        })
        .finally(() => {
          this.loading = false;
        })
    }
  }
}
</script>

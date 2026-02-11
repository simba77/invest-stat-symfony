<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import InputSelect from "@/components/Forms/InputSelect.vue";
import {onMounted, reactive} from "vue";
import {useRoute, useRouter} from "vue-router";

const route = useRoute()
const router = useRouter()
const data = reactive({
  form: {
    id: 0,
    date: '',
    sum: '',
    account: 0,
  },
  accounts: [],
  loading: false,
  errors: null,
  componentKey: 0,
})

function submitForm() {
  data.loading = true;
  const requestUrl = Number(route.params.id) > 0 ? '/api/investments/edit/' + route.params.id : '/api/investments/create'
  axios.post(requestUrl, data.form)
    .then(() => {
      router.push({name: 'Investments'});
    })
    .catch((error) => {
      if (error.response.status === 422 && error.response.data) {
        data.errors = error.response.data;
        data.componentKey += 1;
      } else {
        alert('An error has occurred');
      }
    })
    .finally(() => {
      data.loading = false;
    })
}

function getForm(id: number) {
  data.loading = true;
  axios.get('/api/investments/get-form/' + id)
    .then((response) => {
      Object.assign(data.form, response.data.form)
      data.accounts = response.data.accounts;
      data.componentKey += 1;
    })
    .catch(() => {
      alert('An error has occurred');
    })
    .finally(() => {
      data.loading = false;
    })
}

onMounted(() => {
  getForm(route.params.id ? Number(route.params.id) : 0)
})
</script>

<template>
  <page-component title="Add Deposit">
    <div class="card">
      <div class="card-body py-4">
        <form
          class="space-y-6 w-full md:w-2/3 mx-auto"
          action="#"
          method="POST"
          @submit.prevent="submitForm"
        >
          <div>
            <h3 class="form-title">
              Deposit
            </h3>
            <p class="mt-1 text-sm text-gray-600">
              Enter the date and amount of expense
            </p>
          </div>
          <div class="w-full md:w-2/4">
            <input-text
              :key="data.componentKey"
              v-model="data.form.date"
              :error="data.errors"
              type="date"
              name="date"
              label="Date"
              placeholder="Date"
            />
            <input-text
              :key="data.componentKey"
              v-model.trim="data.form.sum"
              class="mt-3"
              :error="data.errors"
              name="sum"
              label="Amount of Deposit"
              placeholder="Amount of Deposit"
              type="number"
            />
            <input-select
              :key="data.componentKey"
              v-model.number="data.form.account"
              class="mt-3"
              label="Account"
              name="account"
              placeholder="Select Account"
              field-value="id"
              :error="data.errors"
              :options="data.accounts"
            />
          </div>
          <div class="buttons-divider" />
          <div>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="data.loading"
            >
              Save
            </button>
            <router-link
              :to="{name: 'Investments'}"
              class="btn btn-secondary ml-3"
            >
              Back
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </page-component>
</template>

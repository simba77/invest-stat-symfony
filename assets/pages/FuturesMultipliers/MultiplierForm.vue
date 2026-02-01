<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import {onMounted, reactive, ref} from "vue";
import {useRoute, useRouter} from "vue-router";
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";

const route = useRoute()
const router = useRouter()
const componentKey = ref(0)
const formData = reactive({
  id: 0,
  ticker: '',
  value: '',
})

const {run: submitForm, loading, validationErrors} = useAsync(() => axios.request({
    url: Number(route.params.id) > 0 ? '/api/futures/multipliers/update' : '/api/futures/multipliers/create',
    method: Number(route.params.id) > 0 ? 'PATCH' : 'POST',
    data: formData
  })
    .then(() => {
      router.push({name: 'FuturesMultipliers'});
    })
)

const {run: getForm, loading: loadingForm} = useAsync(() => axios.get('/api/futures/multipliers/get-form/' + (route.params.id ?? 0))
  .then((response) => {
    Object.assign(formData, response.data)
  })
)

onMounted(() => {
  getForm()
})
</script>

<template>
  <page-component title="Add Multiplier">
    <div class="card">
      <preloader-component v-if="loadingForm" />
      <form
        v-else
        class="space-y-6 w-full md:w-2/3 mx-auto"
        action="#"
        method="POST"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="form-title">
            Multiplier
          </h3>
          <p class="mt-1 text-sm text-gray-600">
            Enter the ticker and value of the multiplier
          </p>
        </div>
        <div class="w-full md:w-2/4">
          <input-text
            :key="componentKey"
            v-model="formData.ticker"
            :error="validationErrors"
            name="ticker"
            label="Ticker"
            placeholder="Ticker"
          />
          <input-text
            :key="componentKey"
            v-model.trim="formData.value"
            class="mt-3"
            :error="validationErrors"
            name="value"
            label="Multiplier Value"
            placeholder="Multiplier Value"
            type="number"
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
          :to="{name: 'FuturesMultipliers'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

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
  <page-component :title="route.params.id ? 'Edit Multiplier' : 'Add Multiplier'">
    <div class="card">
      <div class="card-body py-4">
        <preloader-component v-if="loadingForm" />
        <form v-else @submit.prevent="submitForm">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-8">
              <div class="mb-3">
                <div class="form-title">
                  Multiplier
                </div>
                <p class="form-description">
                  Enter the ticker and value of the multiplier
                </p>
              </div>
              <div class="form-stack">
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
                  :error="validationErrors"
                  name="value"
                  label="Multiplier Value"
                  placeholder="Multiplier Value"
                  type="number"
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
                  :to="{name: 'FuturesMultipliers'}"
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

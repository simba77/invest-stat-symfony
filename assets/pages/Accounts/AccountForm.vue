<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

import PageComponent from '@/components/PageComponent.vue';
import InputText from '@/components/Forms/InputText.vue';

const route = useRoute();
const router = useRouter();

const form = reactive({
  name: '',
  balance: '0',
  usdBalance: '0',
  commission: '0',
  futuresCommission: '0',
  sort: 100,
});

const loading = ref(false);
const errors = ref<Record<string, any> | null>(null);
const componentKey = ref(0);

const submitForm = async () => {
  loading.value = true;
  errors.value = null;
  const requestUrl = route.params.id
    ? `/api/accounts/update/${route.params.id}`
    : '/api/accounts/create';

  try {
    await axios.post(requestUrl, form);
    router.push({ name: 'Accounts' });
  } catch (error: any) {
    if (error.response?.status === 422 && error.response.data) {
      errors.value = error.response.data;
      componentKey.value += 1;
    } else {
      alert('An error has occurred');
    }
  } finally {
    loading.value = false;
  }
};

const getForm = async (id: number) => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/accounts/get-form/${id}`);
    Object.assign(form, response.data);
    componentKey.value += 1;
  } catch {
    alert('An error has occurred');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (route.params.id) {
    getForm(Number(route.params.id));
  }
});
</script>
<template>
  <page-component :title="route.params.id ? 'Edit Account' : 'Add Account'">
    <div class="card">
      <div class="card-body py-4">
        <form
          class="container-fluid"
          @submit.prevent="submitForm"
        >
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-8">
              <!-- Header -->
              <div class="mb-4">
                <h3 class="form-title">
                  Account
                </h3>
                <p class="text-muted">
                  Enter the name of the account to group your assets
                </p>
              </div>

              <!-- Form fields -->
              <div class="mb-4 form-stack">
                <input-text
                  :key="componentKey"
                  v-model="form.name"
                  :error="errors"
                  name="name"
                  label="Account Name"
                  placeholder="Enter an Account Name"
                />

                <input-text
                  :key="componentKey"
                  v-model.trim="form.balance"
                  :error="errors"
                  name="balance"
                  label="Balance"
                  placeholder="Enter Balance"
                  type="number"
                />

                <input-text
                  :key="componentKey"
                  v-model.trim="form.usdBalance"
                  :error="errors"
                  name="usdBalance"
                  label="USD Balance"
                  placeholder="Enter USD Balance"
                  type="number"
                />

                <input-text
                  :key="componentKey"
                  v-model.trim="form.commission"
                  :error="errors"
                  name="commission"
                  label="Commission"
                  placeholder="Commission"
                  type="number"
                />

                <input-text
                  :key="componentKey"
                  v-model.trim="form.futuresCommission"
                  :error="errors"
                  name="futuresCommission"
                  label="Futures Commission"
                  placeholder="Futures Commission"
                  type="number"
                />

                <input-text
                  :key="componentKey"
                  v-model.number="form.sort"
                  :error="errors"
                  type="number"
                  name="sort"
                  label="Sort"
                  placeholder="Sort"
                />
              </div>

              <hr class="my-4">

              <!-- Actions -->
              <div class="d-flex align-items-center">
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
                  :to="{ name: 'Accounts' }"
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


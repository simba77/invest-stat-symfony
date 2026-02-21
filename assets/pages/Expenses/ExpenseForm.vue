<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import {onMounted, reactive} from "vue";
import {useRoute, useRouter} from "vue-router";

const route = useRoute()
const router = useRouter()
const data = reactive({
  form: {
    name: '',
    sum: '',
  },
  loading: false,
  errors: null,
  componentKey: 0,
})

function submitForm() {
  data.loading = true;
  let requestUrl: string;
  if (route.params.id) {
    requestUrl = '/api/expenses/expense/edit/' + route.params.id
  } else {
    requestUrl = '/api/expenses/' + route.params.category + '/create'
  }

  axios.post(requestUrl, data.form)
    .then(() => {
      router.push({name: 'Expenses'});
    })
    .catch((error) => {
      if (error.response.data) {
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
  axios.get('/api/expenses/expense/' + id)
    .then((response) => {
      data.form = response.data;
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
  if (route.params.id) {
    getForm(Number(route.params.id));
  }
})
</script>

<template>
  <page-component title="Add Expense">
    <div class="card">
      <div class="card-body py-4">
        <form @submit.prevent="submitForm">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-8">
              <div>
                <div class="form-title">
                  Expense
                </div>
                <p class="form-description">
                  Enter the name and amount of expense
                </p>
              </div>
              <div class="form-stack">
                <input-text
                  :key="data.componentKey"
                  v-model="data.form.name"
                  :error="data.errors"
                  name="name"
                  label="Category Name"
                  placeholder="Enter a category name"
                />
                <input-text
                  :key="data.componentKey"
                  v-model.trim="data.form.sum"
                  :error="data.errors"
                  name="sum"
                  label="Amount of expense"
                  placeholder="Amount of expense"
                  type="number"
                />
              </div>
              <hr class="my-4">
              <div>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="data.loading"
                >
                  <span
                    v-if="data.loading"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                  />
                  Save
                </button>
                <router-link
                  :to="{name: 'Expenses'}"
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

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
  },
  loading: false,
  errors: undefined,
  componentKey: 0,
})

function submitForm() {
  data.loading = true;
  let requestUrl;
  if (route.params.id) {
    requestUrl = '/api/expenses/category/edit/' + route.params.id
  } else {
    requestUrl = '/api/expenses/category/create'
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
  axios.get('/api/expenses/category/' + id)
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
  <page-component :title="route.params.id ? 'Edit Category' : 'Add Category'">
    <div class="card">
      <div class="card-body py-4">
        <form @submit.prevent="submitForm">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-8">
              <div class="mb-3">
                <div class="form-title">
                  Category
                </div>
                <p class="form-description">
                  Enter the name of the category to group expenses
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

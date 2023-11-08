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
  <page-component title="Add Category">
    <div class="card">
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        action="#"
        method="POST"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            Category
          </h3>
          <p class="mt-1 text-sm text-gray-600">
            Enter the name of the category to group expenses
          </p>
        </div>
        <div class="w-full md:w-2/4">
          <input-text
            :key="data.componentKey"
            v-model="data.form.name"
            :error="data.errors"
            name="name"
            label="Category Name"
            placeholder="Enter a category name"
          />
        </div>
        <div class="border-b" />
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="data.loading"
        >
          Save
        </button>
        <router-link
          :to="{name: 'Expenses'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

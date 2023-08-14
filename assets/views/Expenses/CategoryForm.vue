<template>
  <page-component title="Add Category">
    <div class="card">
      <form class="space-y-6 w-full md:w-2/3 mx-auto" action="#" method="POST" @submit.prevent="submitForm">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Category</h3>
          <p class="mt-1 text-sm text-gray-600">Enter the name of the category to group expenses</p>
        </div>
        <div class="w-full md:w-2/4">
          <input-text
            v-model="form.name"
            :key="componentKey"
            :error="errors"
            name="name"
            label="Category Name"
            placeholder="Enter a category name"
          />
        </div>
        <div class="border-b"></div>
        <button type="submit" class="btn btn-primary" :disabled="loading">Save</button>
        <router-link :to="{name: 'Expenses'}" class="btn btn-secondary ml-3">Back</router-link>
      </form>
    </div>
  </page-component>
</template>

<script lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";

export default {
  name: "CategoryForm",
  components: {InputText, PageComponent},
  data() {
    return {
      form: {
        name: '',
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
      axios.post('/api/expenses/category/create', this.form)
        .then(() => {
          this.$router.push({name: 'Expenses'});
        })
        .catch((error) => {
          if (error.response.data) {
            this.errors = error.response.data;
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
      axios.get('/api/expenses/edit-category/' + id)
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

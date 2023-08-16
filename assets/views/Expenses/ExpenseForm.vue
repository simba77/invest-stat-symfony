<template>
  <page-component title="Add Expense">
    <div class="card">
      <form class="space-y-6 w-full md:w-2/3 mx-auto" action="#" method="POST" @submit.prevent="submitForm">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Expense</h3>
          <p class="mt-1 text-sm text-gray-600">Enter the name and amount of expense</p>
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
          <input-text
            type="number"
            class="mt-3"
            v-model.number="form.sum"
            :key="componentKey"
            :error="errors"
            name="sum"
            label="Amount of expense"
            placeholder="Amount of expense"
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
  name: "ExpenseForm",
  components: {InputText, PageComponent},
  data() {
    return {
      form: {
        name: '',
        sum: '',
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
      let requestUrl = '';
      if (this.$route.params.id) {
        requestUrl = '/api/expenses/expense/edit/' + this.$route.params.id
      } else {
        requestUrl = '/api/expenses/' + this.$route.params.category + '/create'
      }

      axios.post(requestUrl, this.form)
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
      axios.get('/api/expenses/expense/' + id)
        .then((response) => {
          this.form = response.data;
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

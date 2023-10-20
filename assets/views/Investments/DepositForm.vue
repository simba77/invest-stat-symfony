<script lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import InputSelect from "@/components/Forms/InputSelect.vue";

export default {
  name: "ExpenseForm",
  components: {InputSelect, InputText, PageComponent},
  data() {
    return {
      form: {
        id: 0,
        date: '',
        sum: '',
        account: null,
      },
      accounts: [],
      loading: false,
      errors: null,
      componentKey: 0,
    }
  },
  mounted() {
    this.getForm(this.$route.params.id ?? 0);
  },
  methods: {
    submitForm() {
      this.loading = true;
      const requestUrl = this.$route.params.id > 0 ? '/api/investments/edit/' + this.$route.params.id : '/api/investments/create'
      axios.post(requestUrl, this.form)
        .then(() => {
          this.$router.push({name: 'Investments'});
        })
        .catch((error) => {
          if (error.response.status === 422 && error.response.data) {
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
      axios.get('/api/investments/get-form/' + id)
        .then((response) => {
          Object.assign(this.form, response.data.form)
          this.accounts = response.data.accounts;
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

<template>
  <page-component title="Add Deposit">
    <div class="card">
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        action="#"
        method="POST"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            Deposit
          </h3>
          <p class="mt-1 text-sm text-gray-600">
            Enter the date and amount of expense
          </p>
        </div>
        <div class="w-full md:w-2/4">
          <input-text
            :key="componentKey"
            v-model="form.date"
            :error="errors"
            type="date"
            name="date"
            label="Date"
            placeholder="Date"
          />
          <input-text
            :key="componentKey"
            v-model.number="form.sum"
            type="number"
            class="mt-3"
            :error="errors"
            name="sum"
            label="Amount of Deposit"
            placeholder="Amount of Deposit"
          />
          <input-select
            :key="componentKey"
            v-model.number="form.account"
            class="mt-3"
            label="Account"
            name="account"
            placeholder="Select Account"
            field-value="id"
            :error="errors"
            :options="accounts"
          />
        </div>
        <div class="border-b" />
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="loading"
        >
          Save
        </button>
        <router-link
          :to="{name: 'Investments'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </div>
  </page-component>
</template>

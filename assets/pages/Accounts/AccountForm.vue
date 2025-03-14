<script lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import Panel from "primevue/panel";
import Button from "primevue/button";

export default {
  name: "CategoryForm",
  components: {Button, Panel, InputText, PageComponent},
  data() {
    return {
      form: {
        name: '',
        balance: '0',
        usdBalance: '0',
        commission: '0',
        futuresCommission: '0',
        sort: 100,
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
      const requestUrl = this.$route.params.id ? '/api/accounts/update/' + this.$route.params.id : '/api/accounts/create'
      axios.post(requestUrl, this.form)
        .then(() => {
          this.$router.push({name: 'Accounts'});
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
      axios.get('/api/accounts/get-form/' + id)
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

<template>
  <page-component title="Add Account">
    <Panel>
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        action="#"
        method="POST"
        @submit.prevent="submitForm"
      >
        <div>
          <h3 class="form-title">
            Account
          </h3>
          <p class="mt-1 text-sm text-gray-600">
            Enter the name of the account to group your assets
          </p>
        </div>
        <div class="w-full md:w-2/4">
          <input-text
            :key="componentKey"
            v-model="form.name"
            :error="errors"
            class="mb-3"
            name="name"
            label="Account Name"
            placeholder="Enter an Account Name"
          />
          <input-text
            :key="componentKey"
            v-model.trim="form.balance"
            :error="errors"
            class="mb-3"
            name="balance"
            label="Balance"
            placeholder="Enter Balance"
          />
          <input-text
            :key="componentKey"
            v-model.trim="form.usdBalance"
            :error="errors"
            class="mb-3"
            name="usdBalance"
            label="USD Balance"
            placeholder="Enter USD Balance"
          />
          <input-text
            :key="componentKey"
            v-model.trim="form.commission"
            :error="errors"
            class="mb-3"
            name="commission"
            label="Commission"
            placeholder="Commission"
          />

          <input-text
            :key="componentKey"
            v-model.trim="form.futuresCommission"
            :error="errors"
            class="mb-3"
            name="futuresCommission"
            label="Futures Commission"
            placeholder="Futures Commission"
          />

          <input-text
            :key="componentKey"
            v-model.number="form.sort"
            :error="errors"
            class="mb-3"
            type="number"
            name="sort"
            label="Sort"
            placeholder="Sort"
          />
        </div>
        <div class="buttons-divider" />
        <Button
          type="submit"
          class="btn btn-primary"
          :loading="loading"
          label="Save"
        />
        <router-link
          :to="{name: 'Accounts'}"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </Panel>
  </page-component>
</template>

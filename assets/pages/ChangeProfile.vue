<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import {reactive, ref} from 'vue'
import useAsync from '@/utils/use-async'
import {authStore} from "@/stores/authStore";
import Panel from 'primevue/panel';
import Button from 'primevue/button';

const {userData, checkAuth } = authStore();
const errors = ref(null)
const success = ref(false)
const componentKey = ref(0)
const form = reactive({
  name: userData?.name,
  email: userData?.email,
  salary: userData?.salary,
  password: '',
})

const {loading, run: submitForm} = useAsync(() => {
  return axios.post('/api/change-profile', form)
    .then(() => {
      checkAuth()
      success.value = true
    })
    .catch((error) => {
      if (error.response.status === 422 && error.response.data) {
        errors.value = error.response.data
        componentKey.value += 1
      } else {
        throw error
      }
    })
})

</script>

<template>
  <page-component title="Change Profile">
    <Panel>
      <form
        class="space-y-6 w-full md:w-2/3 mx-auto"
        action="#"
        method="POST"
        @submit.prevent="submitForm"
      >
        <div class="w-full md:w-2/4">
          <div
            v-if="success"
            class="bg-green-600 text-white rounded px-4 py-2"
          >
            Your profile has been successfully changed
          </div>
          <input-text
            :key="componentKey"
            v-model="form.name"
            :error="errors"
            :required="true"
            class="mt-3"
            name="name"
            label="Name"
            placeholder="Name"
          />
          <input-text
            :key="componentKey"
            v-model="form.email"
            :error="errors"
            :required="true"
            class="mt-3"
            type="email"
            name="email"
            label="E-mail"
            placeholder="E-mail"
          />
          <input-text
            :key="componentKey"
            v-model="form.salary"
            :error="errors"
            :required="true"
            type="number"
            step="1"
            class="mt-3"
            name="salary"
            label="Salary"
            placeholder="Salary"
          />
          <input-text
            :key="componentKey"
            v-model="form.password"
            :error="errors"
            type="password"
            class="mt-3"
            name="password"
            label="Password"
            placeholder="Password"
            help="Enter a new password if you need to change it"
          />
        </div>
        <div class="border-b" />
        <Button
          type="submit"
          class="btn btn-primary"
          :loading="loading"
          label="Save"
        />
        <router-link
          to="/"
          class="btn btn-secondary ml-3"
        >
          Back
        </router-link>
      </form>
    </Panel>
  </page-component>
</template>

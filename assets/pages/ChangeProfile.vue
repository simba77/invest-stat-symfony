<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";
import {reactive, ref} from 'vue'
import useAsync from '@/utils/use-async'
import {authStore} from "@/stores/authStore";

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
    <div class="card">
      <div class="card-body py-4">
        <form
          class="row justify-content-center g-3"
          @submit.prevent="submitForm"
        >
          <div class="col-12 col-md-8 col-lg-8">
            <!-- Success message -->
            <div
              v-if="success"
              class="alert alert-success"
            >
              Your profile has been successfully changed
            </div>

            <div class="form-stack">
              <input-text
                :key="componentKey"
                v-model="form.name"
                :error="errors"
                :required="true"
                name="name"
                label="Name"
                placeholder="Name"
              />

              <input-text
                :key="componentKey"
                v-model="form.email"
                :error="errors"
                :required="true"
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
                name="salary"
                label="Salary"
                placeholder="Salary"
              />

              <input-text
                :key="componentKey"
                v-model="form.password"
                :error="errors"
                type="password"
                name="password"
                label="Password"
                placeholder="Password"
                help="Enter a new password if you need to change it"
              />
            </div>

            <hr class="my-4">

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
                to="/"
                class="btn btn-secondary ms-3"
              >
                Back
              </router-link>
            </div>
          </div>
        </form>
      </div>
    </div>
  </page-component>
</template>


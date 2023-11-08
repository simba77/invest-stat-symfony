<script setup lang="ts">
import {LockClosedIcon} from '@heroicons/vue/24/solid'
import axios from "axios";
import {authStore} from "@/stores/authStore";
import {reactive} from "vue";
import {useRouter} from "vue-router";

const router = useRouter()
const data = reactive({
  form: {
    username: '',
    password: '',
    remember_me: true,
  },
  loading: false,
  error: null,
})

function authorize() {
  data.loading = true;
  axios.post('/api/login', data.form)
    .then(() => {
      // Check auth and redirect to homepage
      authStore()
        .checkAuth()
        .then(() => {
          router.push({name: 'HomePage'});
        })
        .catch(() => {
          alert('An error has occurred');
        });
    })
    .catch((error) => {
      if (error.response.status === 401) {
        data.error = error.response.data.error;
      } else {
        alert('An error has occurred');
      }
    })
    .finally(() => {
      data.loading = false;
    })
}
</script>

<template>
  <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <img
          class="mx-auto h-12 w-auto"
          src="../images/workflow-mark-indigo-600.svg"
          alt="Workflow"
        >
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in to your account
        </h2>
      </div>
      <form
        class="mt-8 space-y-6"
        action="#"
        method="POST"
        @submit.prevent="authorize"
      >
        <div
          v-if="data.error"
          class="bg-red-500 text-white rounded px-4 py-2"
        >
          {{ data.error }}
        </div>

        <div class="rounded-md shadow-sm -space-y-px">
          <div class="mb-4">
            <label
              for="email-address"
              class="sr-only"
            >Email address</label>
            <input
              id="email-address"
              v-model="data.form.username"
              name="email"
              type="email"
              autocomplete="email"
              class="form-input"
              placeholder="Email address"
              required
            >
          </div>
          <div>
            <label
              for="password"
              class="sr-only"
            >Password</label>
            <input
              id="password"
              v-model="data.form.password"
              name="password"
              type="password"
              autocomplete="current-password"
              class="form-input"
              placeholder="Password"
              required
            >
          </div>
        </div>

        <div class="flex items-center">
          <input
            id="remember-me"
            v-model="data.form.remember_me"
            name="remember-me"
            type="checkbox"
            class="form-checkbox"
          >
          <label
            for="remember-me"
            class="form-checkbox-label"
          >Remember me</label>
        </div>

        <button
          type="submit"
          class="btn btn-primary relative w-full"
          :disabled="data.loading"
        >
          <span class="absolute left-0 inset-y-0 flex items-center pl-3">
            <LockClosedIcon
              class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400"
              aria-hidden="true"
            />
          </span>
          Sign in
        </button>
      </form>
    </div>
  </div>
</template>

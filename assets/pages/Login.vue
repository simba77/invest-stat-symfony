<script setup lang="ts">
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
  error: null as string | null,
})

function authorize() {
  data.loading = true;

  axios.post('/api/login', data.form)
    .then(() => {
      // Check auth and redirect to homepage
      authStore()
        .checkAuth()
        .then(() => {
          router.push({name: 'Dashboard'});
        })
        .catch(() => {
          alert('An error has occurred');
        });
    })
    .catch((error) => {
      if (error.response?.status === 401) {
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
  <div class="d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <h2 class="fw-bold">
            Sign in to your account
          </h2>
        </div>

        <form @submit.prevent="authorize">
          <div
            v-if="data.error"
            class="alert alert-danger"
          >
            {{ data.error }}
          </div>

          <div class="mb-3">
            <label for="email-address" class="form-label">
              Email address
            </label>
            <input
              id="email-address"
              v-model="data.form.username"
              type="email"
              class="form-control"
              autocomplete="email"
              required
            >
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">
              Password
            </label>
            <input
              id="password"
              v-model="data.form.password"
              type="password"
              class="form-control"
              autocomplete="current-password"
              required
            >
          </div>

          <div class="form-check mb-3">
            <input
              id="remember-me"
              v-model="data.form.remember_me"
              type="checkbox"
              class="form-check-input"
            >
            <label
              for="remember-me"
              class="form-check-label"
            >
              Remember me
            </label>
          </div>

          <button
            type="submit"
            class="btn btn-primary w-100"
            :disabled="data.loading"
          >
            <span
              v-if="data.loading"
              class="spinner-border spinner-border-sm me-2"
              role="status"
            />
            Sign in
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

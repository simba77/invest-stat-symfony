<template>
  <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
    <div class="sm:flex sm:items-start">
      <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
        <banknotes-icon class="h-6 w-6 text-green-600" aria-hidden="true"/>
      </div>
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">Sell Asset: {{ sellAsset.name }}</DialogTitle>
        <div class="mt-2">
          <input-text
            v-model="formData.price"
            class="mb-3"
            name="name"
            label="Sell Price"
            placeholder="Enter an account name"
          />
        </div>
      </div>
    </div>
  </div>
  <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
    <button
      type="button"
      class="btn btn-success mr-3 md:mr-0"
      @click="sendForm"
      :disabled="loading"
    >Sell
    </button>
    <button
      type="button"
      class="btn btn-secondary mr-3"
      @click="close" ref="cancelButtonRef">Cancel
    </button>
  </div>
</template>

<script setup lang="ts">
import {DialogTitle} from '@headlessui/vue'
import {BanknotesIcon} from '@heroicons/vue/24/outline'
import InputText from "@/components/Forms/InputText.vue";
import axios from "axios";

let props = defineProps(['close', 'confirm', 'title', 'text', 'sellAsset']);
let loading = false;

let formData = {
  price: props.sellAsset.price,
}

function sendForm() {
  loading = true;
  axios.post('/api/assets/sell/' + props.sellAsset.id, formData)
    .then(() => {
      props.confirm();
      props.close();
    })
    .catch(() => {
      alert('Something wrong')
    })
    .finally(() => {
      loading = false;
    });
}

</script>

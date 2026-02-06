<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {useModal} from "@/composable/useModal";
import { useNumbers } from "@/composable/useNumbers";
import {usePage} from "@/composable/usePage";
import Button from 'primevue/button';
import {ref} from "vue";

const modal = useModal()
const {formatPrice} = useNumbers()
const {setPageTitle} = usePage()

const loading = ref(false)

setPageTitle("ПАО Сбербанк")
</script>

<template>
  <page-component title="ПАО Сбербанк">
    <preloader-component v-if="loading" />
    <div v-else>
      <div class="flex flex-nowrap gap-2">
        <div>
          Цена:
          350Р
        </div>
        <div class="grow">
          Изменение за день
        </div>
        <div>
          <Button severity="primary">
            Купить
          </Button>
        </div>
        <div>
          <Button severity="secondary">
            Продать
          </Button>
        </div>
      </div>
    </div>
  </page-component>
</template>

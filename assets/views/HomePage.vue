<script lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import StatCard from "@/components/Cards/StatCard.vue";
import axios from "axios";

export default {
  name: "HomePage",
  components: {StatCard, PageComponent},
  data() {
    return {
      loading: true,
      data: {},
      brokers: []
    }
  },
  mounted() {
    this.getData();
  },
  methods: {
    getData() {
      this.loading = true;
      axios.get('/api/dashboard')
        .then((response) => {
          this.data = response.data;
          this.brokers = response.data.brokers;
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
  <page-component title="Dashboard">
    <div v-if="loading">Loading Data...</div>
    <template v-else>
      <div class="flex justify-between">
        <div class="text-xl mb-3">Investment Result</div>
        <div class="text-xl mb-3">1 USD = {{ data.usd }}₽</div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4">
        <stat-card
          v-for="(card, i) in data.summary"
          :key="i"
          :name="card.name"
          :help-text="card.helpText ?? null"
          :percent="card.percent ?? null"
          :total="card.total"
        ></stat-card>
      </div>
    </template>
  </page-component>
</template>


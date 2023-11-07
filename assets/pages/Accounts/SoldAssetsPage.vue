<script lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import axios from "axios";
import helpers from "@/helpers";

export default {
  name: "AccountsPage",
  components: {PageComponent},
  data() {
    return {
      loading: true,
      deleting: false,
      stat: {},
      deleteItem: {
        type: '',
        data: {},
      },
      sell: {},
      assetsGroup: {},
      assets: {},
      helpers,
    }
  },
  mounted() {
    this.getItems();
  },
  methods: {
    getItems() {
      this.loading = true;
      axios.get('/api/assets/sold')
        .then((response) => {
          this.assets = response.data;
        })
        .catch(() => {
          alert('An error has occurred');
        })
        .finally(() => {
          this.loading = false;
        })
    },
  }
}
</script>

<template>
  <page-component title="Sold Assets">
    <div class="mb-6">
      <div
        v-for="(account, index) in assets.data"
        :key="index"
      >
        <!-- Account block -->
        <div class="flex justify-between mb-2 mt-5 py-3 rounded">
          <div class="font-extrabold text-lg">
            {{ account.name }}
          </div>
        </div>
        <!-- // Account block -->

        <div class="w-full overflow-x-auto">
          <template v-if="account.assets.length > 0">
            <table class="simple-table sub-table white-header">
              <thead>
                <tr>
                  <th>Ticker</th>
                  <th>Name</th>
                  <th>Quantity</th>
                  <th>Buy Price</th>
                  <th>Sell Price</th>
                  <th>Full Buy Price</th>
                  <th>Full Sell Price</th>
                  <th>Profit</th>
                </tr>
              </thead>
              <tbody>
                <template
                  v-for="(asset, i) in account.assets"
                  :key="i"
                >
                  <!-- Account block -->
                  <tr
                    v-if="asset.isSubTotal"
                    class="font-bold"
                  >
                    <td :class="[asset.isSubTotal || asset.isTotal ? 'text-right' : '']">
                      {{ asset.ticker }}
                    </td>
                    <td>{{ asset.name }}</td>
                    <td />
                    <td />
                    <td />
                    <td>{{ helpers.formatPrice(asset.fullBuyPrice) }} {{ asset.currency }}</td>
                    <td>{{ helpers.formatPrice(asset.fullSellPrice) }} {{ asset.currency }}</td>
                    <td :class="[asset.profit > 0 ? 'text-green-600' : 'text-red-700']">
                      {{ helpers.formatPrice(asset.profit) }} {{ asset.currency }} ({{ asset.profitPercent }}%)
                    </td>
                  </tr>
                  <!-- Asset block -->
                  <template v-else>
                    <!-- Group of assets -->
                    <template v-if="asset.items.length > 0">
                      <tr
                        class="tr-clickable"
                        @click="asset.showItems = !asset.showItems"
                      >
                        <td :class="[asset.isSubTotal || asset.isTotal ? 'text-right' : '']">
                          {{ asset.ticker }}
                        </td>
                        <td>{{ asset.name }}</td>
                        <td>{{ asset.quantity }}</td>
                        <td>{{ helpers.formatPrice(asset.buyPrice) }} {{ asset.currency }}</td>
                        <td>{{ helpers.formatPrice(asset.sellPrice) }} {{ asset.currency }}</td>
                        <td>{{ helpers.formatPrice(asset.fullBuyPrice) }} {{ asset.currency }}</td>
                        <td>{{ helpers.formatPrice(asset.fullSellPrice) }} {{ asset.currency }}</td>
                        <td :class="[asset.profit > 0 ? 'text-green-600' : 'text-red-700']">
                          {{ asset.profit > 0 ? '+' : '-' }} {{ helpers.formatPrice(Math.abs(asset.profit)) }} {{ asset.currency }} ({{ asset.profitPercent }}%)
                        </td>
                      </tr>
                      <tr v-if="asset.showItems">
                        <td
                          colspan="111"
                          class="!p-2 !bg-white"
                        >
                          <table class="simple-table sub-table white-header">
                            <thead>
                              <tr>
                                <th>Ticker</th>
                                <th>Name</th>
                                <th>Quantity</th>
                                <th>Buy Price</th>
                                <th>Sell Price</th>
                                <th>Full Buy Price</th>
                                <th>Full Sell Price</th>
                                <th>Profit</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="(subItem, subIndex) in asset.items"
                                :key="'sub' + subIndex"
                              >
                                <td :class="[subItem.isSubTotal || subItem.isTotal ? 'text-right' : '']">
                                  {{ subItem.ticker }}
                                  <span
                                    v-if="subItem.isShort"
                                    class="bg-red-200 text-red-900 rounded-full inline-flex ml-2 pr-2 pl-2 items-center"
                                  >short</span>
                                </td>
                                <td>{{ subItem.name }}</td>
                                <td>{{ subItem.quantity }}</td>
                                <td>{{ helpers.formatPrice(subItem.buyPrice) }} {{ subItem.currency }}</td>
                                <td>{{ helpers.formatPrice(subItem.sellPrice) }} {{ subItem.currency }}</td>
                                <td>{{ helpers.formatPrice(subItem.fullBuyPrice) }} {{ subItem.currency }}</td>
                                <td>{{ helpers.formatPrice(subItem.fullSellPrice) }} {{ subItem.currency }}</td>
                                <td :class="[subItem.profit > 0 ? 'text-green-600' : 'text-red-700']">
                                  {{ subItem.profit > 0 ? '+' : '-' }} {{ helpers.formatPrice(Math.abs(subItem.profit)) }} {{ subItem.currency }} ({{ subItem.profitPercent }}%)
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                      </tr>
                    </template>
                    <!-- Asset without group -->
                    <tr v-else>
                      <td :class="[asset.isSubTotal || asset.isTotal ? 'text-right' : '']">
                        {{ asset.ticker }}
                        <span
                          v-if="asset.isShort"
                          class="bg-red-200 text-red-900 rounded-full inline-flex ml-2 pr-2 pl-2 items-center"
                        >short</span>
                      </td>
                      <td>{{ asset.name }}</td>
                      <td>{{ asset.quantity }}</td>
                      <td>{{ helpers.formatPrice(asset.buyPrice) }} {{ asset.currency }}</td>
                      <td>{{ helpers.formatPrice(asset.price) }} {{ asset.currency }}</td>
                      <td>{{ helpers.formatPrice(asset.fullBuyPrice) }} {{ asset.currency }}</td>
                      <td>{{ helpers.formatPrice(asset.fullSellPrice) }} {{ asset.currency }}</td>
                      <td :class="[asset.profit > 0 ? 'text-green-600' : 'text-red-700']">
                        {{ asset.profit > 0 ? '+' : '-' }} {{ helpers.formatPrice(Math.abs(asset.profit)) }} {{ asset.currency }} ({{ asset.profitPercent }}%)
                      </td>
                    </tr>
                  </template>
                </template>
              </tbody>
            </table>
          </template>
          <div v-else>
            <div class="opacity-60 text-sm">
              The List is Empty
            </div>
          </div>
        </div>
      </div>
    </div>
  </page-component>
</template>

<template>
  <page-component title="Accounts">
    <template v-if="stat">
      <div class="text-xl mb-3">Summary</div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4 mb-5">
        <stat-card
          v-for="(card, i) in stat.summary"
          :key="i"
          :name="card.name"
          :help-text="card.helpText ?? null"
          :percent="card.percent ?? null"
          :total="card.total"
        ></stat-card>
      </div>
    </template>

    <div class="mb-4">
      <router-link :to="{name: 'CreateAccount'}" class="btn btn-primary">Create Account</router-link>
      <button class="btn btn-secondary ml-3" :disabled="loading" @click="updateData">Update Data</button>
    </div>

    <div class="mb-6">
      <div v-for="(account, index) in assets.data" :key="index">

        <!-- Account block -->
        <div class="flex justify-between mb-2 mt-5 py-3 rounded">
          <div class="">
            <div class="font-extrabold text-lg">{{ account.name }}</div>
            <div class="text-sm">
              <span class="font-light">Balance:</span> <span>{{ helpers.formatPrice(account.balance) }} {{ account.currency }}</span>
              <span class="font-light ml-3">Deposits:</span> <span>{{ helpers.formatPrice(account.deposits) }} ₽</span>
              <span class="font-light ml-3">Current Value:</span> <span>{{ helpers.formatPrice(account.currentValue) }} ₽</span>
              <span class="font-light ml-3">Profit: </span>
              <span :class="[account.fullProfit > 0 ? 'text-green-600' : 'text-red-700']">
                {{ helpers.formatPrice(account.fullProfit) }} ₽
              </span>
            </div>
          </div>
          <div class="flex items-center">
            <router-link
              :to="{name: 'AddAsset', params: {account: account.id}}"
              class="text-gray-300 hover:text-gray-600 mr-2"
              title="Add Asset">
              <plus-circle-icon class="h-5 w-5"></plus-circle-icon>
            </router-link>
            <router-link
              :to="{name: 'EditAccount', params: {id: account.id}}"
              class="text-gray-300 hover:text-gray-600 mr-2"
              title="Edit Account"
            >
              <pencil-icon class="h-5 w-5"></pencil-icon>
            </router-link>
            <button
              type="button"
              class="text-gray-300 hover:text-red-500"
              @click="openConfirmModal(account, 'category')"
              title="Delete Account"
            >
              <x-circle-icon class="h-5 w-5"></x-circle-icon>
            </button>
          </div>
        </div>
        <!-- // Account block -->

        <div class="w-full overflow-x-auto">
          <template v-if="account.assets.length > 0">
            <table class="simple-table sub-table white-header">
              <thead>
              <tr>
                <th>Name</th>
                <th>Quantity</th>
                <th>Buy Price</th>
                <th>Current Price</th>
                <th>Target Price</th>
                <th>
                  Profit
                  <div class="text-xs text-gray-400">(percent, commission)</div>
                </th>
                <th>Target Profit</th>
                <th>Percent</th>
                <th class="flex justify-end" style="min-width: 115px;">Actions</th>
              </tr>
              </thead>
              <tbody>

              <template v-for="(asset, i) in account.assets" :key="i">

                <!-- Total row -->
                <tr v-if="asset.isSubTotal" class="font-bold">
                  <td>{{ asset.name }}</td>
                  <td></td>

                  <td>{{ helpers.formatPrice(asset.fullBuyPrice) }} {{ asset.currency }}</td>
                  <td>{{ helpers.formatPrice(asset.fullPrice) }} {{ asset.currency }}</td>
                  <td></td>
                  <td :class="[asset.profit > 0 ? 'text-green-600' : 'text-red-700']">
                    <div>{{ helpers.formatPrice(asset.profit) }} {{ asset.currency }}</div>
                    <div class="text-xs">({{ asset.profitPercent }}%)</div>
                  </td>
                  <td></td>
                  <td></td>
                  <td class="table-actions"></td>
                </tr>
                <!-- Asset block -->
                <template v-else>

                  <!-- Group of assets -->
                  <template v-if="asset.items.length > 0">

                    <!-- Parent row with assets -->
                    <tr class="tr-clickable" @click="asset.showItems = !asset.showItems">
                      <td
                        :class="[asset.isSubTotal || asset.isTotal ? 'text-right' : '', 'underline']"
                        v-tooltip="'Last Update: ' + asset.updated"
                      >
                        <div class="font-extrabold">{{ asset.name }}</div>
                        <div class="text-gray-500">
                          <span class="text-xs">{{ asset.ticker }}</span>
                          <span v-if="asset.isShort" class="bg-red-200 text-red-900 rounded-full inline-flex pr-2 pl-2 items-center ml-2">short</span>
                        </div>
                      </td>
                      <td>{{ asset.quantity }}</td>
                      <td>
                        <div>{{ helpers.formatPrice(asset.buyPrice) }} {{ asset.currency }}</div>
                        <div class="text-xs text-gray-500">{{ helpers.formatPrice(asset.fullBuyPrice) }} {{ asset.currency }}</div>
                      </td>
                      <td>
                        <div>{{ helpers.formatPrice(asset.price) }} {{ asset.currency }}</div>
                        <div class="text-xs text-gray-500">{{ helpers.formatPrice(asset.fullPrice) }} {{ asset.currency }}</div>
                      </td>
                      <td>
                        <template v-if="asset.targetPrice">
                          <div>{{ helpers.formatPrice(asset.targetPrice) }} {{ asset.currency }}</div>
                          <div class="text-xs text-gray-500">{{ helpers.formatPrice(asset.fullTargetPrice) }} {{ asset.currency }}</div>
                        </template>
                        <template v-else>&mdash;</template>
                      </td>
                      <td :class="[asset.profit > 0 ? 'text-green-600' : 'text-red-700']">
                        <div>{{ formatProfit(asset) }}</div>
                        <div class="text-xs">({{ asset.profitPercent }}%, {{ asset.commission }}{{ asset.currency }})</div>
                      </td>
                      <td v-html="formatTargetProfit(asset)"></td>
                      <td>{{ asset.accountPercent }}%</td>
                      <td class="table-actions"></td>
                    </tr>

                    <!-- Children table -->
                    <tr v-if="asset.showItems">
                      <td colspan="111" class="!p-2 !bg-white">
                        <table class="simple-table sub-table white-header">
                          <thead>
                          <tr>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Buy Price</th>
                            <th>Current Price</th>
                            <th>Target</th>
                            <th>
                              Profit
                              <div class="text-xs text-gray-400">(percent, commission)</div>
                            </th>
                            <th>Target Profit</th>
                            <th>Percent</th>
                            <th class="flex justify-end" style="min-width: 115px;">Actions</th>
                          </tr>
                          </thead>
                          <tbody>
                          <tr v-for="(subItem, subIndex) in asset.items" :key="'sub' + subIndex">
                            <td>
                              <div>
                                <div class="font-extrabold">{{ subItem.name }}</div>
                                <div class="text-xs text-gray-500">
                                  <span>{{ subItem.ticker }}</span>
                                  <span v-if="subItem.isShort" class="bg-red-200 text-red-900 rounded-full inline-flex pr-2 pl-2 items-center ml-2">short</span>
                                </div>
                              </div>
                            </td>
                            <td>{{ subItem.quantity }}</td>
                            <td>
                              <div>{{ helpers.formatPrice(subItem.buyPrice) }} {{ subItem.currency }}</div>
                              <div class="text-xs text-gray-500">{{ helpers.formatPrice(subItem.fullBuyPrice) }} {{ subItem.currency }}</div>
                            </td>
                            <td>
                              <div>{{ helpers.formatPrice(subItem.price) }} {{ subItem.currency }}</div>
                              <div class="text-xs text-gray-500">{{ helpers.formatPrice(subItem.fullPrice) }} {{ subItem.currency }}</div>
                            </td>
                            <td>
                              <template v-if="subItem.targetPrice">
                                <div>{{ helpers.formatPrice(subItem.targetPrice) }} {{ subItem.currency }}</div>
                                <div class="text-xs text-gray-500">{{ helpers.formatPrice(subItem.fullTargetPrice) }} {{ subItem.currency }}</div>
                              </template>
                              <template v-else>&mdash;</template>
                            </td>
                            <td :class="[subItem.profit > 0 ? 'text-green-600' : 'text-red-700']">
                              <div>{{ formatProfit(subItem) }}</div>
                              <div class="text-xs">({{ subItem.profitPercent }}%, {{ subItem.commission }}{{ subItem.currency }})</div>
                            </td>
                            <td v-html="formatTargetProfit(subItem)"></td>
                            <td>{{ subItem.accountPercent }}%</td>
                            <td class="table-actions">
                              <div class="flex justify-end items-center show-on-row-hover">
                                <router-link
                                  :to="{name: 'EditAsset', params: {id: subItem.id, account: account.id}}"
                                  class="text-gray-300 hover:text-gray-600 mr-2"
                                  title="Edit"
                                >
                                  <pencil-icon class="h-5 w-5"></pencil-icon>
                                </router-link>
                                <div
                                  @click="openSellModal(subItem)"
                                  class="text-gray-300 hover:text-gray-600 mr-2 cursor-pointer"
                                  title="Sell"
                                >
                                  <banknotes-icon class="h-5 w-5"></banknotes-icon>
                                </div>
                                <button
                                  type="button"
                                  class="text-gray-300 hover:text-red-500"
                                  @click="openConfirmModal(subItem, 'asset')"
                                  title="Delete"
                                >
                                  <x-circle-icon class="h-5 w-5"></x-circle-icon>
                                </button>
                              </div>
                            </td>
                          </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </template>

                  <!-- Asset without group -->
                  <tr v-else>
                    <td
                      :class="[asset.isSubTotal || asset.isTotal ? 'text-right' : '']"
                      v-tooltip="'Last Update: ' + asset.updated"
                    >
                      <div>
                        <div class="font-extrabold">{{ asset.name }}</div>
                        <div class="text-xs text-gray-500">
                          <span>{{ asset.ticker }}</span>
                          <span v-if="asset.isShort" class="bg-red-200 text-red-900 rounded-full inline-flex ml-2 pr-2 pl-2 items-center">short</span>
                        </div>
                      </div>
                    </td>
                    <td>{{ asset.quantity }}</td>
                    <td>
                      <div>{{ helpers.formatPrice(asset.buyPrice) }} {{ asset.currency }}</div>
                      <div class="text-xs text-gray-500">{{ helpers.formatPrice(asset.fullBuyPrice) }} {{ asset.currency }}</div>
                    </td>
                    <td>
                      <div>{{ helpers.formatPrice(asset.price) }} {{ asset.currency }}</div>
                      <div class="text-xs text-gray-500">{{ helpers.formatPrice(asset.fullPrice) }} {{ asset.currency }}</div>
                    </td>
                    <td>
                      <template v-if="asset.targetPrice">
                        <div>{{ helpers.formatPrice(asset.targetPrice) }} {{ asset.currency }}</div>
                        <div class="text-xs text-gray-500">{{ helpers.formatPrice(asset.fullTargetPrice) }} {{ asset.currency }}</div>
                      </template>
                      <template v-else>&mdash;</template>
                    </td>
                    <td :class="[asset.profit > 0 ? 'text-green-600' : 'text-red-700']">
                      <div>{{ formatProfit(asset) }}</div>
                      <div class="text-xs">({{ asset.profitPercent }}%, {{ asset.commission }}{{ asset.currency }})</div>
                    </td>
                    <td v-html="formatTargetProfit(asset)"></td>
                    <td>{{ asset.accountPercent }}%</td>
                    <td class="table-actions">
                      <template v-if="asset.id">
                        <div class="flex justify-end items-center show-on-row-hover">
                          <router-link
                            :to="{name: 'EditAsset', params: {id: asset.id, account: account.id}}"
                            class="text-gray-300 hover:text-gray-600 mr-2"
                            title="Edit"
                          >
                            <pencil-icon class="h-5 w-5"></pencil-icon>
                          </router-link>
                          <div
                            @click="openSellModal(asset)"
                            class="text-gray-300 hover:text-gray-600 mr-2 cursor-pointer"
                            title="Sell"
                          >
                            <banknotes-icon class="h-5 w-5"></banknotes-icon>
                          </div>
                          <button
                            type="button"
                            class="text-gray-300 hover:text-red-500"
                            @click="openConfirmModal(asset, 'asset')"
                            title="Delete"
                          >
                            <x-circle-icon class="h-5 w-5"></x-circle-icon>
                          </button>
                        </div>
                      </template>
                    </td>
                  </tr>
                </template>
              </template>
              </tbody>
            </table>
          </template>
        </div>
      </div>
    </div>
  </page-component>

  <base-modal ref="deleteConfirmationModal">
    <confirm-modal
      :close="closeModal"
      :confirm="confirmDeletion"
      title="Deletion confirmation"
      :text="'Are you sure you want to delete &quot;<b>'+ deleteItem.data.name +'</b>&quot;?'"
    ></confirm-modal>
  </base-modal>
  <base-modal ref="sellModal">
    <sell-modal :close="closeSellModal" :confirm="getItems" :sellAsset="sell"></sell-modal>
  </base-modal>
</template>

<script lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import axios from "axios";
import {PencilIcon, XCircleIcon, PlusCircleIcon, BanknotesIcon} from "@heroicons/vue/24/outline";
import BaseModal from "@/components/Modals/BaseModal.vue";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";
import StatCard from "@/components/Cards/StatCard.vue";
import helpers from "@/helpers";
import SellModal from "@/components/Modals/SellModal.vue";

export default {
  name: "AccountsPage",
  components: {SellModal, StatCard, ConfirmModal, BaseModal, PageComponent, PencilIcon, XCircleIcon, PlusCircleIcon, BanknotesIcon},
  mounted() {
    this.getItems();
    this.getStat();
  },
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
  methods: {
    formatProfit(asset: { profit: number; currency: string; }) {
      return (asset.profit > 0 ? '+' : '-') + ' ' + this.helpers.formatPrice(Math.abs(asset.profit)) + ' ' + asset.currency;
    },

    formatTargetProfit(asset: { targetPrice: number; buyPrice: number; quantity: number; currency: string; fullBuyPrice: number; }) {
      if (!asset.targetPrice) {
        return '';
      }

      let fullTarget = (asset.targetPrice - asset.buyPrice) * asset.quantity;
      return this.helpers.formatPrice(asset.targetPrice - asset.buyPrice) + ' ' + asset.currency + '' +
        '<div class="text-xs">(' + this.helpers.formatPrice(fullTarget) + ' ' + asset.currency +
        ', ' + this.helpers.formatPrice(fullTarget / asset.fullBuyPrice * 100) + '%)</div>';
    },

    openConfirmModal(item: any, type = 'expense') {
      this.deleteItem.type = type;
      this.deleteItem.data = item;
      setTimeout(() => {
        this.$refs.deleteConfirmationModal.openModal();
      });
    },

    openSellModal(item: any) {
      this.sell = item;
      setTimeout(() => {
        this.$refs.sellModal.openModal();
      });
    },

    closeModal() {
      this.$refs.deleteConfirmationModal.closeModal();
    },

    closeSellModal() {
      this.$refs.sellModal.closeModal();
    },

    confirmDeletion() {
      if (this.deleteItem.type === 'category') {
        this.deleteCategory(this.deleteItem.data.id)
          .finally(() => {
            this.closeModal();
          });
      } else {
        this.deleteAsset(this.deleteItem.data.id)
          .finally(() => {
            this.closeModal();
          });
      }
    },

    getItems() {
      this.loading = true;
      axios.get('/api/accounts/list')
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

    getStat() {
      this.loading = true;
      axios.get('/api/accounts/summary')
        .then((response) => {
          this.stat = response.data;
        })
        .catch(() => {
          alert('An error has occurred');
        })
        .finally(() => {
          this.loading = false;
        })
    },

    deleteCategory(id: number) {
      this.deleting = true;
      return new Promise((resolve, reject) => {
        axios.post('/api/accounts/delete/' + id)
          .then(() => {
            this.getItems();
            resolve({deleted: true});
          })
          .catch(() => {
            alert('An error has occurred');
            reject('An error has occurred');
          })
          .finally(() => {
            this.deleting = false;
          })
      });
    },

    deleteAsset(id: number) {
      this.deleting = true;
      return new Promise((resolve, reject) => {
        axios.post('/api/assets/delete/' + id)
          .then(() => {
            this.getItems();
            resolve({deleted: true});
          })
          .catch(() => {
            alert('An error has occurred');
            reject('An error has occurred');
          })
          .finally(() => {
            this.deleting = false;
          })
      });
    },

    updateData() {
      this.loading = true;
      axios.get('/api/accounts/update-data')
        .then(() => {
          this.getItems();
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

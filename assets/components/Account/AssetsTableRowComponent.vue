<template>
  <tr :class="{'tr-clickable': clickable}" @click="emit('showChildren')">
    <td
      :class="{
      'text-right': item.isSubTotal || item.isTotal,
      'underline': clickable
    }"
      v-tooltip="'Last Update: ' + item.updatedAt">
      <div class="font-extrabold">
        <lock-closed-icon class="h-3 w-3 inline-block" v-if="item.blocked"></lock-closed-icon>
        {{ item.name }}
      </div>
      <div class="text-gray-500">
        <span class="text-xs">{{ item.ticker }}</span>
        <span v-if="item.isShort" class="bg-red-200 text-red-900 rounded-full inline-flex pr-2 pl-2 items-center ml-2">short</span>
      </div>
    </td>
    <td>{{ item.quantity }}</td>
    <td>
      <div>{{ helpers.formatPrice(item.avgBuyPrice) }} {{ item.currency }}</div>
      <div class="text-xs text-gray-500">{{ helpers.formatPrice(item.fullBuyPrice) }} {{ item.currency }}</div>
    </td>
    <td>
      <div>{{ helpers.formatPrice(item.currentPrice) }} {{ item.currency }}</div>
      <div class="text-xs text-gray-500">{{ helpers.formatPrice(item.fullCurrentPrice) }} {{ item.currency }}</div>
    </td>
    <td>
      <template v-if="item.avgTargetPrice">
        <div>{{ helpers.formatPrice(item.avgTargetPrice) }} {{ item.currency }}</div>
        <div class="text-xs text-gray-500">{{ helpers.formatPrice(item.fullTargetPrice) }} {{ item.currency }}</div>
      </template>
      <template v-else>&mdash;</template>
    </td>
    <td :class="[item.profit > 0 ? 'text-green-600' : 'text-red-700']">
      <div>{{ formatProfit(item) }}</div>
      <div class="text-xs">({{ item.profitPercent }}%, {{ item.fullCommission }} {{ item.currency }})</div>
    </td>
    <td>
      <template v-if="item.targetProfit !== 0">
        {{ item.targetProfit }} {{ item.currency }}
        <div class="text-xs">
          ({{ helpers.formatPrice(item.fullTargetProfit) }} {{ item.currency }}, {{ item.fullTargetProfitPercent }}%)
        </div>
      </template>
      <template v-else>&mdash;</template>
    </td>
    <td>{{ item.groupPercent }}%</td>
    <td class="table-actions">
      <div v-if="showActions" class="flex justify-end items-center show-on-row-hover">
        <router-link
          :to="{name: 'EditAsset', params: {id: item.id, account: item.accountId}}"
          class="text-gray-300 hover:text-gray-600 mr-2"
          title="Edit"
        >
          <pencil-icon class="h-5 w-5"></pencil-icon>
        </router-link>
        <div
          @click="sellAssetModal(item)"
          class="text-gray-300 hover:text-gray-600 mr-2 cursor-pointer"
          title="Sell"
        >
          <cash-icon class="h-5 w-5"></cash-icon>
        </div>
        <button
          type="button"
          class="text-gray-300 hover:text-red-500"
          @click="confirmDeletionAsset(item, () => getAccounts(item.accountId))"
          title="Delete"
        >
          <x-circle-icon class="h-5 w-5"></x-circle-icon>
        </button>
      </div>
    </td>
  </tr>
</template>
<script setup lang="ts">
import {inject} from 'vue'
import {LockClosedIcon, PencilIcon, XCircleIcon, CashIcon} from "@heroicons/vue/outline";
import helpers from "../../helpers";
import type {Asset, AssetsGroup} from "@/models/account";
import {useAssets} from "@/composable/useAssets";
import {useModal} from "@/composable/useModal";
import SellModal from "@/components/Modals/SellModal.vue";

function formatProfit(asset: { profit: number; currency: string; }) {
  return (asset.profit > 0 ? '+' : '-') + ' ' + helpers.formatPrice(Math.abs(asset.profit)) + ' ' + asset.currency;
}

const emit = defineEmits<{ showChildren: boolean }>()

defineProps<{
  item: Asset | AssetsGroup,
  showActions?: boolean,
  clickable?: boolean,
}>();

const {confirmDeletion: confirmDeletionAsset, sellAsset} = useAssets()
const {getAccounts} = inject('accounts')
const modal = useModal()

function sellAssetModal(item: Asset | AssetsGroup) {
  modal.open(
    SellModal,
    {price: item.currentPrice, name: item.name},
    [
      {
        label: 'Sell',
        classes: ['btn-success mr-3 md:mr-0 ml-3'],
        callback: async (model: { price: number }) => {
          await sellAsset(item.id, model.price)
          getAccounts(item.accountId)
          modal.close()
        },
      },
      {
        label: 'Cancel',
        classes: ['btn-secondary'],
        callback: () => modal.close(),
      }
    ]
  );
}

</script>

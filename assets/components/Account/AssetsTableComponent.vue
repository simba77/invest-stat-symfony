<template>
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

    <template v-for="(asset, i) in assets" :key="i">

      <!-- Total row -->
      <tr v-if="asset.isSubTotal" class="font-bold">
        <td>Subtotal:</td>
        <td></td>
        <td>
          <div v-if="!asset.isBaseCurrency">{{ helpers.formatPrice(asset.fullBuyPrice) }} $</div>
          <div>{{ helpers.formatPrice(asset.fullBuyPriceConverted) }} ₽</div>
        </td>
        <td>
          <div v-if="!asset.isBaseCurrency">{{ helpers.formatPrice(asset.fullCurrentPrice) }} $</div>
          <div>{{ helpers.formatPrice(asset.fullCurrentPriceConverted) }} ₽</div>
        </td>
        <td></td>
        <td :class="[asset.profit > 0 ? 'text-green-600' : 'text-red-700']">
          <div v-if="!asset.isBaseCurrency">{{ helpers.formatPrice(asset.profit) }} $</div>
          <div>{{ helpers.formatPrice(asset.profitConverted) }} ₽</div>
          <div class="text-xs">({{ asset.profitPercent }}%)</div>
        </td>
        <td></td>
        <td></td>
        <td class="table-actions"></td>
      </tr>

      <!-- Asset block -->
      <template v-else>

        <!-- Group of assets -->
        <template v-if="asset.items.length > 1">

          <!-- Parent row with assets -->
          <assets-table-row-component
            :item="asset"
            :clickable="true"
            @showChildren="() => asset.showItems = !asset.showItems"
          />

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
                <template v-for="(subItem, subIndex) in asset.items" :key="'sub' + subIndex">
                  <assets-table-row-component :item="subItem" :show-actions="true"></assets-table-row-component>
                </template>
                </tbody>
              </table>
            </td>
          </tr>
        </template>

        <!-- Asset without group -->
        <template v-else>
          <assets-table-row-component :item="asset" :show-actions="true"></assets-table-row-component>
        </template>

      </template>
    </template>
    </tbody>
  </table>
</template>
<script setup lang="ts">
import helpers from "../../helpers";
import AssetsTableRowComponent from "@/components/Account/AssetsTableRowComponent.vue";
import {computed} from "vue";
import {AssetsGroup} from "@/models/account";

const props = defineProps(['modelValue'])
const emit = defineEmits(['update:modelValue'])
const assets = computed<AssetsGroup[]>({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})
</script>

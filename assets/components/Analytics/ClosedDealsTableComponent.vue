<script setup lang="ts">
import {useDealsGroup} from "@/composable/useDealsGroup";

import ClosedDealsGroupComponent from "@/components/Analytics/ClosedDealsGroupComponent.vue";
import ClosedDealsItemComponent from "@/components/Analytics/ClosedDealsItemComponent.vue";
import {ClosedDealsListItem, ClosedDealsSummary} from "@/types/analytics";
import helpers from "../../helpers";

defineProps<{ assets: ClosedDealsListItem[], summary: ClosedDealsSummary }>()

const dealsGroup = useDealsGroup()

</script>

<template>
  <table class="simple-table sub-table white-header">
    <thead>
      <tr>
        <th>Name</th>
        <th>Quantity</th>
        <th>Buy Price</th>
        <th>Sell Price</th>
        <th>
          Profit
          <div class="text-xs text-gray-400">
            (percent, commission)
          </div>
        </th>
      </tr>
    </thead>

    <tbody>
      <template
        v-for="(asset, i) in assets"
        :key="i"
      >
        <!-- Asset block -->
        <!-- Parent row with assets -->
        <closed-deals-group-component
          :item="asset.groupData"
          :clickable="true"
          @show-children="dealsGroup.toggleGroup(asset.groupData.ticker)"
        />

        <!-- Children table -->
        <tr v-if="dealsGroup.openedGroups.value[asset.groupData.ticker]">
          <td
            colspan="111"
            class="!p-2 !bg-white"
          >
            <table class="simple-table sub-table white-header">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Quantity</th>
                  <th>Buy Price</th>
                  <th>Sell Price</th>
                  <th>
                    Profit
                    <div class="text-xs text-gray-400">
                      (percent, commission)
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <template
                  v-for="(subItem, subIndex) in asset.deals"
                  :key="'sub' + subIndex"
                >
                  <closed-deals-item-component :item="subItem" />
                </template>
              </tbody>
            </table>
          </td>
        </tr>
      </template>

      <!-- Total row -->
      <tr class="font-bold">
        <td>Total (base currency):</td>
        <td />
        <td>
          <div>{{ helpers.formatPrice(summary.buyPrice) }} ₽</div>
        </td>
        <td>
          <div>{{ helpers.formatPrice(summary.sellPrice) }} ₽</div>
        </td>
        <td :class="[summary.profit > 0 ? 'text-green-600' : 'text-red-700']">
          <div>{{ helpers.formatPrice(summary.profit) }} ₽</div>
          <div class="text-xs">
            ({{ summary.profitPercent }}%)
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</template>


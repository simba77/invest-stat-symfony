<script setup lang="ts">
import AssetsTableRowComponent from "@/components/Account/AssetsTableRowComponent.vue";
import { AssetsGroupData, GroupSummary } from "@/types/account";
import AssetsTableGroupComponent from "@/components/Account/AssetsTableGroupComponent.vue";
import { useDealsGroup } from "@/composable/useDealsGroup";
import { useNumbers } from "@/composable/useNumbers";

const {formatPrice, formatPercent} = useNumbers()

defineProps<{
  assets: { [key: string]: AssetsGroupData },
  summary: GroupSummary,
  hideActions?: boolean
}>()

const dealsGroup = useDealsGroup()

</script>

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
          <div class="text-xs text-gray-400">
            (percent, commission)
          </div>
        </th>
        <th>Target Profit</th>
        <th>Percent</th>
        <th
          v-if="!hideActions"
          class="flex justify-end"
          style="min-width: 115px;"
        >
          Actions
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
        <assets-table-group-component
          :item="asset.groupData"
          :hide-actions="hideActions"
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
                  <th>Current Price</th>
                  <th>Target</th>
                  <th>
                    Profit
                    <div class="text-xs text-gray-400">
                      (percent, commission)
                    </div>
                  </th>
                  <th>Target Profit</th>
                  <th>Percent</th>
                  <th
                    v-if="!hideActions"
                    class="flex justify-end"
                    style="min-width: 115px;"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                <template
                  v-for="(subItem, subIndex) in asset.deals"
                  :key="'sub' + subIndex"
                >
                  <assets-table-row-component :hide-actions="hideActions" :item="subItem" />
                </template>
              </tbody>
            </table>
          </td>
        </tr>
      </template>

      <!-- Total row -->
      <tr class="font-bold">
        <td>Subtotal:</td>
        <td />
        <td>
          <div v-if="!summary.isBaseCurrency">
            {{ formatPrice(summary.buyPrice, '$') }}
          </div>
          <div>{{ formatPrice(summary.buyPriceInBaseCurrency, '₽') }}</div>
        </td>
        <td>
          <div v-if="!summary.isBaseCurrency">
            {{ formatPrice(summary.currentPrice, '$') }}
          </div>
          <div>{{ formatPrice(summary.currentPriceInBaseCurrency, '₽') }} </div>
        </td>
        <td />
        <td :class="[summary.profit > 0 ? 'text-green-600' : 'text-red-700']">
          <div v-if="!summary.isBaseCurrency">
            {{ formatPrice(summary.profit, '$') }}
          </div>
          <div>{{ formatPrice(summary.profitInBaseCurrency, '₽') }}</div>
          <div class="text-xs">
            ({{ formatPercent(summary.profitPercent) }})
          </div>
        </td>
        <td />
        <td />
        <td v-if="!hideActions" class="table-actions" />
      </tr>
    </tbody>
  </table>
</template>


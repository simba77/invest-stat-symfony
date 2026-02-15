<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {useModal} from "@/composable/useModal";
import { useNumbers } from "@/composable/useNumbers";
import {usePage} from "@/composable/usePage";
import {ref} from "vue";
import {
  Building2, TrendingUp, TrendingDown,
  Wallet, Plus, Pencil, Trash2, ExternalLink,
  MoveRight
} from 'lucide-vue-next';
import useAsync from "@/utils/use-async";
import axios from "axios";
import {useRoute} from "vue-router";
import {ShowShareResponseDTO} from "@/types/instruments";

const route = useRoute()
const modal = useModal()
const {formatPrice} = useNumbers()
const {setPageTitle} = usePage()

setPageTitle('Просмотр данных по инструменту')

const instrumentData = ref<ShowShareResponseDTO | null>(null)

const {run, loading} = useAsync(() => axios.get('/api/instrument/share/' + route.params.id)
  .then((response) => {
    instrumentData.value = response.data
    setPageTitle(instrumentData.value?.name ?? 'Просмотр данных по инструменту')
  })
)

run()

const openPositions = [
  { date: '2023-10-15', account: 'Брокерский счет №1', type: 'Покупка', price: '4 100', qty: 10, total: '41 000' },
  { date: '2023-11-01', account: 'Брокерский счет №1', type: 'Покупка', price: '4 200', qty: 20, total: '84 000' },
  { date: '2023-12-10', account: 'ИИС (Сбер)', type: 'Покупка', price: '4 300', qty: 20, total: '86 000' },
];

const closedPositions = [
  { openDate: '2023-05-10', account: 'Брокерский счет №1', closeDate: '2023-08-20', openPrice: '3 800', closePrice: '4 100', qty: 15, profit: 4500 },
  { openDate: '2023-02-15', account: 'Брокерский счет №2', closeDate: '2023-04-05', openPrice: '3 500', closePrice: '3 400', qty: 10, profit: -1000 },
];

const dividends = [
  { date: '2023-09-15', account: 'Брокерский счет №1', tax: '-585', amount: '3 915' },
  { date: '2023-06-20', account: 'ИИС (Сбер)', tax: '0', amount: '8 000' },
];

</script>

<template>
  <page-component>
    <preloader-component v-if="loading" />
    <div v-else-if="instrumentData">
      <div class="container-fluid py-4 min-vh-100">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div class="d-flex align-items-center">
            <div class="bg-white p-2 rounded shadow-sm me-3">
              <Building2 :size="32" class="text-secondary" />
            </div>
            <div>
              <h1 class="h3 mb-0 fw-bold">
                {{ instrumentData.ticker }}
              </h1>
              <p class="text-muted mb-0">
                {{ instrumentData.name }}
              </p>
            </div>
          </div>
          <div class="d-flex gap-2">
            <span class="badge bg-dark text-white border py-2 px-3">{{ instrumentData.marketName }}</span>
            <span class="badge border-dark text-white border py-2 px-3">Акция</span>
          </div>
        </div>

        <p class="small text-muted mb-4">
          Тикер: <span class="fw-bold">{{ instrumentData.ticker }}</span> | Лот: {{ instrumentData.lotSize }} | ISIN: {{ instrumentData.isin }}
        </p>

        <div class="row g-3 mb-5">
          <!-- Текущие данные по инструменту -->
          <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Текущая цена</span>
                <trending-up v-if="instrumentData.priceTrend === 'up'" :size="16" class="text-success" />
                <trending-down v-else-if="instrumentData.priceTrend === 'down'" :size="16" class="text-danger" />
                <move-right v-else :size="16" />
              </div>
              <h4 class="fw-bold mb-1" :class="{'text-success': instrumentData.priceTrend === 'up', 'text-danger': instrumentData.priceTrend === 'down'}">
                {{ formatPrice(+instrumentData.price, instrumentData.currency) }}
              </h4>
              <span class="text-muted x-small">
                {{ formatPrice(+instrumentData.difference, instrumentData.currency) }}
                ({{ instrumentData.priceTrend === 'up' ? '+' : '' }}{{ (+instrumentData.percent).toFixed(2) }}%) за сегодня
              </span>
            </div>
          </div>

          <!-- Количество и стоимость в портфеле -->
          <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">В портфеле</span>
              </div>
              <h4 class="fw-bold mb-1">
                {{ instrumentData.portfolio.quantity }} шт.
              </h4>
              <span class="text-muted x-small">
                Общая ст-сть:
                {{ formatPrice(+instrumentData.portfolio.fullPrice, instrumentData.currency) }}
              </span>
            </div>
          </div>

          <!-- Прибыль по открытым позициям в портфеле -->
          <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Прибыль</span>
                <trending-up v-if="instrumentData.portfolio.fullProfitTrend === 'up'" :size="16" class="text-success" />
                <trending-down v-else-if="instrumentData.portfolio.fullProfitTrend === 'down'" :size="16" class="text-danger" />
                <move-right v-else :size="16" />
              </div>
              <h4 class="fw-bold mb-1" :class="{'text-success': instrumentData.portfolio.fullProfitTrend === 'up', 'text-danger': instrumentData.portfolio.fullProfitTrend === 'down'}">
                {{ instrumentData.portfolio.fullProfitTrend === 'up' ? '+' : '' }}{{ (+instrumentData.portfolio.fullProfitPercent).toFixed(2) }}%
              </h4>
              <span class="text-muted x-small">
                {{ instrumentData.portfolio.fullProfitTrend === 'up' ? '+' : '' }}{{ formatPrice(+instrumentData.portfolio.fullProfit, instrumentData.currency) }}
              </span>
            </div>
          </div>

          <!-- Доля в портфеле -->
          <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Доля в портфеле</span>
              </div>
              <h4 class="fw-bold mb-1">
                {{ (+instrumentData.portfolio.portfolioPercent).toFixed(2) }}%
              </h4>
              <span class="text-muted x-small">
                Средняя цена:
                {{ formatPrice(+instrumentData.portfolio.averageBuyPrice, instrumentData.currency) }}
              </span>
            </div>
          </div>

          <!-- Прибыль по закрытым позициям в портфеле -->
          <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Результат сделок</span>
                <trending-up v-if="instrumentData.portfolio.closedDealsProfitTrend === 'up'" :size="16" class="text-success" />
                <trending-down v-else-if="instrumentData.portfolio.closedDealsProfitTrend === 'down'" :size="16" class="text-danger" />
                <move-right v-else :size="16" />
              </div>
              <h4 class="fw-bold mb-1" :class="{'text-success': instrumentData.portfolio.closedDealsProfitTrend === 'up', 'text-danger': instrumentData.portfolio.closedDealsProfitTrend === 'down'}">
                {{ formatPrice(+instrumentData.portfolio.closedDealsProfit, instrumentData.currency) }}
              </h4>
              <span class="text-muted x-small">
                {{ instrumentData.portfolio.closedDealsProfitTrend === 'up' ? '+' : '' }}{{ (+instrumentData.portfolio.closedDealsProfitPercent).toFixed(2) }}%
              </span>
            </div>
          </div>

          <!-- Сумма полученных дивидендов -->
          <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Дивиденды</span>
                <wallet :size="16" />
              </div>
              <h4 class="fw-bold mb-1">
                {{ formatPrice(+instrumentData.portfolio.sumOfDividends, instrumentData.currency) }}
              </h4>
              <span class="text-muted x-small">
                Получено за всё время
              </span>
            </div>
          </div>
        </div>

        <section class="mb-5">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
              Открытые позиции
            </h5>
            <button class="btn btn-primary btn-sm d-flex align-items-center gap-2">
              <Plus :size="16" /> Добавить позицию
            </button>
          </div>
          <div class="card border-0 shadow-sm">
            <div class="table-responsive">
              <table class="table table-dark simple-table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Дата</th>
                    <th>Счет</th>
                    <th>Тип операции</th>
                    <th>Цена</th>
                    <th>Кол-во</th>
                    <th>Объем</th>
                    <th class="text-end">
                      Действия
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pos in openPositions" :key="pos.date">
                    <td>{{ pos.date }}</td>
                    <td>{{ pos.account }}</td>
                    <td><span class="badge bg-dark">{{ pos.type }}</span></td>
                    <td>{{ pos.price }} ₽</td>
                    <td>{{ pos.qty }}</td>
                    <td>{{ pos.total }} ₽</td>
                    <td class="text-end text-muted">
                      <div class="d-flex justify-content-end">
                        <Pencil :size="16" class="me-3 cursor-pointer" />
                        <Trash2 :size="16" class="me-3 cursor-pointer" />
                        <ExternalLink :size="16" class="cursor-pointer" />
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="mb-5">
          <h5 class="fw-bold mb-3">
            Закрытые позиции
          </h5>
          <div class="card border-0 shadow-sm">
            <div class="table-responsive">
              <table class="table table-dark simple-table align-middle mb-0 text-nowrap">
                <thead>
                  <tr>
                    <th>Дата открытия</th>
                    <th>Счет</th>
                    <th>Дата закрытия</th>
                    <th>Цена откр.</th>
                    <th>Цена закр.</th>
                    <th>Кол-во</th>
                    <th class="text-end">
                      Фин. результат
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pos in closedPositions" :key="pos.openDate">
                    <td>{{ pos.openDate }}</td>
                    <td>{{ pos.account }}</td>
                    <td>{{ pos.closeDate }}</td>
                    <td>{{ pos.openPrice }} ₽</td>
                    <td>{{ pos.closePrice }} ₽</td>
                    <td>{{ pos.qty }}</td>
                    <td class="text-end fw-bold" :class="pos.profit > 0 ? 'text-success' : 'text-danger'">
                      {{ pos.profit > 0 ? '↗' : '↘' }} {{ Math.abs(pos.profit) }} ₽
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
              Дивиденды
            </h5>
            <button class="btn btn-primary btn-sm d-flex align-items-center gap-2">
              <Plus :size="16" /> Добавить дивиденд
            </button>
          </div>
          <div class="card border-0 shadow-sm">
            <div class="table-responsive">
              <table class="table table-dark simple-table  align-middle mb-0">
                <thead>
                  <tr>
                    <th>Дата выплаты</th>
                    <th>Счет</th>
                    <th class="text-end">
                      Налог
                    </th>
                    <th class="text-end">
                      Сумма на руки
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="div in dividends" :key="div.date">
                    <td>{{ div.date }}</td>
                    <td>{{ div.account }}</td>
                    <td class="text-end text-muted small">
                      {{ div.tax }} ₽
                    </td>
                    <td class="text-end fw-bold text-success">
                      {{ div.amount }} ₽
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
    </div>
    <div v-else>
      <div class="alert alert-danger">
        Инструмент не найден
      </div>
    </div>
  </page-component>
</template>

<style scoped>
.x-small { font-size: 0.75rem; }
.cursor-pointer { cursor: pointer; transition: opacity 0.2s; }
.cursor-pointer:hover { opacity: 0.7; }
.card { border-radius: 12px; }
.table thead th {
  font-weight: 500;
  text-transform: none;
  border-top: none;
}
</style>

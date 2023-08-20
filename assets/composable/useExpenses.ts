import {ref} from "vue";
import {Expenses} from "@/models/expenses";
import axios from "axios";
import useAsync from "@/utils/use-async";

const expenses = ref<Expenses[]>([])
const summary = ref({})

export const useExpenses = () => {
  /**
   * Получение списка расходов
   */
  async function getExpenses() {
    expenses.value = await axios.get('/api/expenses').then((response) => response.data);
  }

  /**
   * Получение суммарной информации
   */
  async function getSummary() {
    summary.value = await axios.get('/api/expenses/summary').then((response) => response.data.summary);
  }

  // Delete expense with the specific id
  async function deleteExpense(id: number) {
    await axios.post('/api/expenses/expense/delete/' + id).then((response) => response.data);
  }

  // Delete expenses category with the specific id
  async function deleteExpensesCategory(id: number) {
    await axios.post('/api/expenses/category/delete/' + id).then((response) => response.data);
  }

  const {loading: loadingExpenses, run: asyncGetExpenses} = useAsync(getExpenses)
  const {loading: loadingSummary, run: asyncGetSummary} = useAsync(getSummary)

  return {
    expenses,
    summary,
    loadingExpenses,
    loadingSummary,
    deleteExpense,
    deleteExpensesCategory,
    getExpenses: asyncGetExpenses,
    getSummary: asyncGetSummary,
  }
}

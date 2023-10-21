import {createRouter, createWebHistory, RouteRecordRaw} from "vue-router";

const routes: Array<RouteRecordRaw> = [
  {
    name: 'HomePage',
    path: '/',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/HomePage.vue')
  },
  {
    name: 'Investments',
    path: '/investments',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/InvestmentsPage.vue')
  },
  {
    name: 'AddDeposit',
    path: '/investments/add-deposit',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Investments/DepositForm.vue')
  },
  {
    name: 'EditDeposit',
    path: '/accounts/edit-deposit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Investments/DepositForm.vue')
  },

  {
    name: 'Expenses',
    path: '/expenses',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/ExpensesPage.vue')
  },
  {
    name: 'CreateCategory',
    path: '/expenses/create-category',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Expenses/CategoryForm.vue')
  },
  {
    name: 'EditCategory',
    path: '/expenses/edit-category/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Expenses/CategoryForm.vue')
  },
  {
    name: 'AddExpense',
    path: '/expenses/add-expense/:category',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Expenses/ExpenseForm.vue')
  },
  {
    name: 'EditExpense',
    path: '/expenses/edit-expense/:category/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Expenses/ExpenseForm.vue')
  },
  {
    name: 'Login',
    path: '/login',
    meta: {
      onlyGuests: true,
    },
    component: () => import('./views/LoginPage.vue')
  },

  // Accounts
  {
    name: 'Accounts',
    path: '/accounts',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Accounts/AccountsPage.vue')
  },
  {
    name: 'CreateAccount',
    path: '/accounts/create-account',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Accounts/AccountForm.vue')
  },
  {
    name: 'EditAccount',
    path: '/accounts/edit-accounts/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Accounts/AccountForm.vue')
  },
  {
    name: 'AccountDetail',
    path: '/accounts/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Accounts/AccountDetail.vue')
  },

  // Assets
  {
    name: 'AddAsset',
    path: '/accounts/assets/add/:account',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Accounts/AssetForm.vue')
  },
  {
    name: 'EditAsset',
    path: '/accounts/assets/edit/:account/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Accounts/AssetForm.vue')
  },
  {
    name: 'SoldAssets',
    path: '/sold-assets',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Accounts/SoldAssetsPage.vue')
  },

  // Savings
  {
    name: 'DepositAccounts',
    path: '/deposits/accounts',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Deposits/DepositAccountsPage.vue')
  },
  {
    name: 'DepositAccountsCreate',
    path: '/deposits/accounts/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Deposits/DepositAccountForm.vue')
  },
  {
    name: 'DepositAccountsEdit',
    path: '/deposits/accounts/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Deposits/DepositAccountForm.vue')
  },

  // Пополнения и списания с накопительных счетов
  {
    name: 'Deposits',
    path: '/deposits',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Deposits/DepositsPage.vue')
  },
  {
    name: 'DepositCreate',
    path: '/deposits/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Deposits/DepositForm.vue')
  },
  {
    name: 'DepositEdit',
    path: '/deposits/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Deposits/DepositForm.vue')
  },

  {
    name: 'Analytics',
    path: '/analytics',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Analytics/AnalyticsPage.vue')
  },

];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router

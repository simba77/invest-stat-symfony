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
    name: 'SavingAccounts',
    path: '/savings/accounts',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Savings/SavingAccountsPage.vue')
  },
  {
    name: 'SavingAccountsCreate',
    path: '/savings/accounts/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Savings/SavingAccountForm.vue')
  },
  {
    name: 'SavingAccountsEdit',
    path: '/savings/accounts/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Savings/SavingAccountForm.vue')
  },

  // Пополнения и списания с накопительных счетов
  {
    name: 'Savings',
    path: '/savings',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Savings/SavingsPage.vue')
  },
  {
    name: 'SavingCreate',
    path: '/savings/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Savings/SavingForm.vue')
  },
  {
    name: 'SavingEdit',
    path: '/savings/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./views/Savings/SavingForm.vue')
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

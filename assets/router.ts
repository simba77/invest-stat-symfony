import {createRouter, createWebHistory, RouteRecordRaw} from "vue-router";

const routes: Array<RouteRecordRaw> = [
  {
    name: 'Dashboard',
    path: '/',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Index.vue')
  },
  {
    name: 'Investments',
    path: '/investments',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Investments/Index.vue')
  },
  {
    name: 'AddDeposit',
    path: '/investments/add-deposit',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Investments/DepositForm.vue')
  },
  {
    name: 'EditDeposit',
    path: '/accounts/edit-deposit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Investments/DepositForm.vue')
  },

  {
    name: 'Expenses',
    path: '/expenses',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Expenses/Index.vue')
  },
  {
    name: 'CreateCategory',
    path: '/expenses/create-category',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Expenses/CategoryForm.vue')
  },
  {
    name: 'EditCategory',
    path: '/expenses/edit-category/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Expenses/CategoryForm.vue')
  },
  {
    name: 'AddExpense',
    path: '/expenses/add-expense/:category',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Expenses/ExpenseForm.vue')
  },
  {
    name: 'EditExpense',
    path: '/expenses/edit-expense/:category/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Expenses/ExpenseForm.vue')
  },
  {
    name: 'Login',
    path: '/login',
    meta: {
      onlyGuests: true,
    },
    component: () => import('./pages/Login.vue')
  },

  // Accounts
  {
    name: 'Accounts',
    path: '/accounts',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Accounts/Index.vue')
  },
  {
    name: 'CreateAccount',
    path: '/accounts/create-account',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Accounts/AccountForm.vue')
  },
  {
    name: 'EditAccount',
    path: '/accounts/edit-accounts/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Accounts/AccountForm.vue')
  },
  {
    name: 'AccountDetail',
    path: '/accounts/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Accounts/AccountDetail.vue')
  },

  // Assets
  {
    name: 'AddAsset',
    path: '/accounts/assets/add/:account',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Accounts/AssetForm.vue')
  },
  {
    name: 'EditAsset',
    path: '/accounts/assets/edit/:account/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/Accounts/AssetForm.vue')
  },
  {
    name: 'ClosedDeals',
    path: '/closed-deals',
    meta: {
      requiresAuth: true,
    },
    component: () => import('./pages/ClosedDeals.vue')
  },

  // Savings
  {
    name: 'DepositAccounts',
    path: '/deposits/accounts',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Deposits/DepositAccountsPage.vue')
  },
  {
    name: 'DepositAccountsCreate',
    path: '/deposits/accounts/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Deposits/DepositAccountForm.vue')
  },
  {
    name: 'DepositAccountsEdit',
    path: '/deposits/accounts/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Deposits/DepositAccountForm.vue')
  },

  // Пополнения и списания с накопительных счетов
  {
    name: 'Deposits',
    path: '/deposits',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Deposits/Index.vue')
  },
  {
    name: 'DepositCreate',
    path: '/deposits/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Deposits/DepositForm.vue')
  },
  {
    name: 'DepositEdit',
    path: '/deposits/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Deposits/DepositForm.vue')
  },

  {
    name: 'Portfolio',
    path: '/portfolio',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Portfolio.vue')
  },

  {
    name: 'ChangeProfile',
    path: '/change-profile',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/ChangeProfile.vue')
  },

  {
    name: 'Dividends',
    path: '/dividends',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Dividends/Index.vue')
  },
  {
    name: 'DividendCreate',
    path: '/dividends/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Dividends/DividendForm.vue')
  },
  {
    name: 'DividendEdit',
    path: '/dividends/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Dividends/DividendForm.vue')
  },

  {
    name: 'Coupons',
    path: '/coupons',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Coupons/Index.vue')
  },
  {
    name: 'CouponCreate',
    path: '/coupons/create',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Coupons/CouponForm.vue')
  },
  {
    name: 'CouponEdit',
    path: '/coupons/edit/:id',
    meta: {
      requiresAuth: true,
    },
    component: () => import('@/pages/Coupons/CouponForm.vue')
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router

import Overview from './modules/Overview/Overview.vue'
import STKPush from './modules/STKPush/STKPush.vue'

export default {
  name: 'SafaricomMobileMoney',
  components: {
    Overview,
    STKPush
  },
  routes: [
    {
      path: '/safaricom-mobile-money',
      name: 'safaricom-mobile-money.overview',
      component: Overview,
      meta: {
        title: 'Safaricom M-PESA Overview'
      }
    },
    {
      path: '/safaricom-mobile-money/stk-push',
      name: 'safaricom-mobile-money.stk-push',
      component: STKPush,
      meta: {
        title: 'Initiate M-PESA Payment'
      }
    }
  ],
  menu: [
    {
      title: 'Safaricom M-PESA',
      icon: 'money-bill',
      children: [
        {
          title: 'Overview',
          route: 'safaricom-mobile-money.overview'
        },
        {
          title: 'Initiate Payment',
          route: 'safaricom-mobile-money.stk-push'
        }
      ]
    }
  ]
} 
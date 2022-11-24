let routes = [
    {
        path: '/wave-money/wave-money-overview',
        component: require('./plugins/wave-money-payment-provider/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/wave-money/payment/:name/:id',
        component: require('./plugins/wave-money-payment-provider/js/components/Payment/Payment').default,
        meta: { layout: 'default' },
    },
    {
        path: '/wave-money/result/:name/:id',
        component: require('./plugins/wave-money-payment-provider/js/components/Payment/Result').default,
        meta: { layout: 'default' },
    }
]
let routes = [
    {
        path: '/kelin-meters/kelin-overview',
        component: require('./plugins/kelin-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-customer',
        component: require('./plugins/kelin-meter/js/components/Customer/List').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter',
        component: require('./plugins/kelin-meter/js/components/Meter/List').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter/status/:meter',
        component: require('./plugins/kelin-meter/js/components/Meter/Status').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter/daily-consumptions/:meter',
        component: require('./plugins/kelin-meter/js/components/Meter/Consumption/Daily').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter/minutely-consumptions/:meter',
        component: require('./plugins/kelin-meter/js/components/Meter/Consumption/Minutely').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-setting',
        component: require('./plugins/kelin-meter/js/components/Setting/Setting').default,
        meta: { layout: 'default' },
    },

]
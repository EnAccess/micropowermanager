let routes = [
    {
        path: '/spark-meters/sm-site',
        component: require('./plugins/spark-meter/js/components/Site/SiteList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-meter-model',
        component: require('./plugins/spark-meter/js/components/MeterModel/MeterModelList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-customer',
        component: require('./plugins/spark-meter/js/components/Customer/CustomerList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-tariff',
        component: require('./plugins/spark-meter/js/components/Tariff/TariffList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-tariff/:id',
        component: require('./plugins/spark-meter/js/components/Tariff/TariffDetail').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-overview',
        component: require('./plugins/spark-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-sales-account',
        component: require('./plugins/spark-meter/js/components/SalesAccount/SalesAccountList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-setting',
        component: require('./plugins/spark-meter/js/components/Setting/Setting').default,
        meta: { layout: 'default' },
    },
]

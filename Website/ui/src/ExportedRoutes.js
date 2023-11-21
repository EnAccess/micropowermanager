import LoginHeader from './modules/Login/LoginHeader'
import LoginFooter from './modules/Login/LoginFooter'
import Payment from './plugins/wave-money-payment-provider/js/modules/Payment/Payment'
import Result from './plugins/wave-money-payment-provider/js/modules/Payment/Result'

import Welcome from './pages/Welcome/index.vue'
import Login from './pages/Login/index.vue'
import Register from './pages/Register/index.vue'
import ForgotPassword from './pages/ForgotPassword/index.vue'

/*eslint-disable */
export const exportedRoutes = [
    {
        path: '/welcome',
        name: 'welcome',
        components: { default: Welcome, header: LoginHeader, footer: LoginFooter },
        props: {
            header: { colorOnScroll: 400 }
        },
        meta: { requireAuth: false }
    },
    {
        path: '/login',
        name: 'login',
        components: { default: Login, header: LoginHeader, footer: LoginFooter },
        props: {
            header: { colorOnScroll: 400 }
        },
        meta: { requireAuth: false }
    },
    {
        path: '/register',
        name: 'register',
        components: { default: Register, header: LoginHeader, footer: LoginFooter },
        props: {
            header: { colorOnScroll: 400 }
        },
        meta: { requireAuth: false }
    },
    {

        path: '/forgot-password',
        name: 'forgot-password',
        components: { default: ForgotPassword, header: LoginHeader, footer: LoginFooter },

        meta: { requireAuth: false }
    },
    {
        path: '/',
        component: require('./pages/Dashboard/index.vue').default,
        name: 'cluster-list-dashboard',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Clusters', link: '/' },
        },
    },
    {
        path: '/dashboards/mini-grid/',
        component: require('./pages/Dashboard/MiniGrid/index.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Mini-Grids', link: '/dashboards/mini-grid' },
        },
    },

    {
        path: '/dashboards/mini-grid/:id',
        component: require('./pages/Dashboard/MiniGrid/_id.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Mini-Grid', link: '/dashboards/mini-grid', target: 'id' },
        },
    },
    {
        path: '/reports',
        component: require('./pages/Report/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/people',
        component: require('./pages/Client/index.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Customers', link: '/people' },
        },
    },
    {
        path: '/people/:id',
        component: require('./pages/Client/_id.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Customers', link: '/people', target: 'id' },
        },
    },
    {
        path: '/transactions',
        component: require('./pages/Transaction/index.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Transactions', link: '/transactions' },
        },
    },
    {
        //transaction list
        path: '/transactions/search',
        component: require('./pages/Transaction/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/transactions/:id',
        component: require('./pages/Transaction/_id.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Transactions', link: '/transactions', target: 'id' },
        },
    },
    {
        path: '/tickets',
        component: require('./pages/Ticket/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/tickets/settings/users',
        component: require('./pages/Ticket/Setting/User/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/tickets/settings/categories',
        component: require('./pages/Ticket/Setting/Category/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/tariffs',
        component: require('./pages/Tariff/index.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Tariffs', link: '/tariffs' },
        },
    },
    {
        path: '/tariffs/:id',
        component: require('./pages/Tariff/_id.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Tariffs', link: '/tariffs', target: 'id' },
        },
    },
    {
        path: '/meters',
        component: require('./pages/Meter/index.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Meters', link: '/meters' },
        },

    },
    {
        path: '/meters/:id',
        component: require('./pages/Meter/_id.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Meters', link: '/meters', target: 'id' },
        },

    },
    {
        path: '/meters/types',
        component: require('./pages/MeterType/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/solar-home-systems',
        component: require('./pages/SolarHomeSystem/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/clusters',
        component: require('./pages/Dashboard/index.vue').default,
        name: 'cluster-list',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Clusters', link: '/clusters' }
        },
    },
    {
        path: '/locations/add-cluster',
        component: require('./pages/Location/Cluster/New/index.vue').default,
        name: 'cluster-new',
        meta: { layout: 'default' },
    },
    {
        path: '/clusters/:id',
        component: require('./pages/Dashboard/Cluster/_id.vue').default,
        name: 'cluster-detail',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Clusters', link: '/clusters', target: 'id' }
        },
    },
    {
        path: '/targets',
        component: require('./pages/Target/index.vue').default,
        name: 'target-list',
        meta: { layout: 'default' },
    },
    {
        path: '/targets/new',
        component: require('./pages/Target/New/index.vue').default,
        name: 'new-target',
        meta: { layout: 'default' },
    },
    {
        path: '/connection-types',
        component: require('./pages/Connection/Type/index.vue').default,
        name: 'connection-types',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Connection Types', link: '/connection-types' },
        },
    },
    {
        path: '/connection-types/:id',
        component: require('./pages/Connection/Type/_id.vue').default,
        name: 'connection-type-detail',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Connection Types', link: '/connection-types', target: 'id' },
        }
    },
    {
        path: '/connection-types/new',
        component: require('./pages/Connection/Type/New/index.vue').default,
        name: 'new-connection-types',
        meta: { layout: 'default' },
    },
    {
        path: '/connection-groups',
        component: require('./pages/Connection/Group/index.vue').default,
        name: 'connection-groups',
        meta: { layout: 'default' },
    },
    {
        path: '/sms/list',
        component: require('./pages/Sms/index.vue').default,
        name: 'sms-list',
        meta: { layout: 'default' },
    },
    {
        path: '/sms/newsms',
        component: require('./pages/Sms/New/index.vue').default,
        name: 'new-sms',
        meta: { layout: 'default' },
    },
    {
        path: '/maintenance',
        component: require('./pages/Maintenance/index.vue').default,
        name: 'maintenance',
        meta: { layout: 'default' },
    },
    {
        path: '/locations/add-village',
        component: require('./pages/Location/Village/New/index.vue').default,
        name: 'add-village',
        meta: { layout: 'default' },
    },
    {
        path: '/locations/add-mini-grid',
        component: require('./pages/Location/MiniGrid/New/index.vue').default,
        name: 'add-mini-grid',
        meta: { layout: 'default' }
    },
    {
        path: '/assets',
        component: require('./pages/Appliance/index.vue').default,
        name: 'asset',
        meta: { layout: 'default' },
    },
    {
        path: '/settings',
        component: require('./pages/Settings/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/profile',
        component: require('./pages/Profile/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/profile/management',
        component: require('./pages/Profile/Management/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/agents',
        component: require('./pages/Agent/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/agents/:id',
        component: require('./pages/Agent/_id.vue').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Agents', link: '/agents', target: 'id' },
        },
    },
    {
        path: '/commissions',
        component: require('./pages/Agent/Commission/index.vue').default,
        meta: { layout: 'default' },
    },
    {
        path: '/sold-appliance-detail/:id',
        component: require('./pages/Client/Appliance/_id.vue').default,
        meta: {
            layout: 'default', breadcrumb: {
                level: 'detail', name: 'Sold Appliance Detail', link: '/sold-appliance-detail', target: 'id'
            }
        }
    },
    {
        path: '/calin-meters/calin-overview',
        component: require('./plugins/calin-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/calin-smart-meters/calin-smart-overview',
        component: require('./plugins/calin-smart-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-overview',
        component: require('./plugins/kelin-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-customer',
        component: require('./plugins/kelin-meter/js/modules/Customer/List').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter',
        component: require('./plugins/kelin-meter/js/modules/Meter/List').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter/status/:meter',
        component: require('./plugins/kelin-meter/js/modules/Meter/Status').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter/daily-consumptions/:meter',
        component: require('./plugins/kelin-meter/js/modules/Meter/Consumption/Daily').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-meter/minutely-consumptions/:meter',
        component: require('./plugins/kelin-meter/js/modules/Meter/Consumption/Minutely').default,
        meta: { layout: 'default' },
    },
    {
        path: '/kelin-meters/kelin-setting',
        component: require('./plugins/kelin-meter/js/modules/Setting/Setting').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-site',
        component: require('./plugins/spark-meter/js/modules/Site/SiteList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-meter-model',
        component: require('./plugins/spark-meter/js/modules/MeterModel/MeterModelList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-customer',
        component: require('./plugins/spark-meter/js/modules/Customer/CustomerList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-tariff',
        component: require('./plugins/spark-meter/js/modules/Tariff/TariffList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-tariff/:id',
        component: require('./plugins/spark-meter/js/modules/Tariff/TariffDetail').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-overview',
        component: require('./plugins/spark-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-sales-account',
        component: require('./plugins/spark-meter/js/modules/SalesAccount/SalesAccountList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/spark-meters/sm-setting',
        component: require('./plugins/spark-meter/js/modules/Setting/Setting').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-overview',
        component: require('./plugins/steama-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-site',
        component: require('./plugins/steama-meter/js/modules/Site/SiteList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-customer',
        component: require('./plugins/steama-meter/js/modules/Customer/CustomerList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-transaction/:customer_id',
        component: require('./plugins/steama-meter/js/modules/Customer/CustomerMovements').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-meter',
        component: require('./plugins/steama-meter/js/modules/Meter/MeterList').default,
        meta: { layout: 'default' },
    },

    {
        path: '/steama-meters/steama-agent',
        component: require('./plugins/steama-meter/js/modules/Agent/AgentList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-setting',
        component: require('./plugins/steama-meter/js/modules/Setting/Setting').default,
        meta: { layout: 'default' },
    },
    {
        path: '/stron-meters/stron-overview',
        component: require('./plugins/stron-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/bulk-registration/bulk-registration',
        component: require('./plugins/bulk-registration/js/modules/Csv').default,
        meta: { layout: 'default' },
    },
    {
        path: '/viber-messaging/viber-overview',
        component: require('./plugins/viber-messaging/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/wave-money/wave-money-overview',
        component: require('./plugins/wave-money-payment-provider/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/wave-money/payment/:name/:id',
        modules: { default: Payment, header: LoginHeader, footer: LoginFooter },
        name: '/wave-money/payment',
        props: {
            header: { colorOnScroll: 400 }
        },
        meta: { requireAuth: false }
    },
    {
        path: '/wave-money/result/:name/:id',
        name: '/wave-money/result',
        modules: { default: Result, header: LoginHeader, footer: LoginFooter },
        props: {
            header: { colorOnScroll: 400 }
        },
        meta: { requireAuth: false }
    },
    {
        path: '/micro-star-meters/micro-star-overview',
        component: require('./plugins/micro-star-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/swifta-payment/swifta-payment-overview',
        component: require('./plugins/swifta-payment-provider/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/sun-king-shs/sun-king-overview',
        component: require('./plugins/sun-king-shs/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/gome-long-meters/gome-long-overview',
        component: require('./plugins/gome-long-meter/js/modules/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/wavecom/transactions',
        component: require('@/plugins/wavecom-payment-provider/js/modules/Component.vue').default,
        meta: { layout: 'default' },
    },
]

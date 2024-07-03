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
        components: {
            default: Welcome,
            header: LoginHeader,
            footer: LoginFooter,
        },
        props: {
            header: {colorOnScroll: 400},
        },
        meta: {requireAuth: false},
    },
    {
        path: '/login',
        name: 'login',
        components: {
            default: Login,
            header: LoginHeader,
            footer: LoginFooter,
        },
        props: {
            header: {colorOnScroll: 400},
        },
        meta: {requireAuth: false},
    },
    {
        path: '/register',
        name: 'register',
        components: {
            default: Register,
            header: LoginHeader,
            footer: LoginFooter,
        },
        props: {
            header: {colorOnScroll: 400},
        },
        meta: {requireAuth: false},
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        components: {
            default: ForgotPassword,
            header: LoginHeader,
            footer: LoginFooter,
        },

        meta: {requireAuth: false},
    },
    {
        path: '/',
        component: require('./pages/Dashboard/index.vue').default,
        name: 'cluster-list-dashboard',
        meta: {
            layout: 'default',
            breadcrumb: {level: 'base', name: 'Clusters', link: '/'},
        },
    },
    {
        path: '/dashboards/mini-grid/:id',
        component: require('./pages/Dashboard/MiniGrid/_id.vue').default,
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Mini-Grid',
                link: '/dashboards/mini-grid',
                target: 'id',
            },
        },
    },
    {
        path: '/people/:id',
        component: require('./pages/Client/_id.vue').default,
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Customers',
                link: '/people',
                target: 'id',
            },
        },
    },
    {
        path: '/transactions/:id',
        component: require('./pages/Transaction/_id.vue').default,
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Transactions',
                link: '/transactions',
                target: 'id',
            },
        },
    },
    {
        path: '/tariffs/:id',
        component: require('./pages/Tariff/_id.vue').default,
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Tariffs',
                link: '/tariffs',
                target: 'id',
            },
        },
    },
    {
        path: '/meters/:id',
        component: require('./pages/Meter/_id.vue').default,
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Meters',
                link: '/meters',
                target: 'id',
            },
        },
    },
    {
        path: '/locations/add-cluster',
        component: require('./pages/Location/Cluster/New/index.vue').default,
        name: 'cluster-new',
        meta: {layout: 'default'},
    },
    {
        path: '/clusters/:id',
        component: require('./pages/Dashboard/Cluster/_id.vue').default,
        name: 'cluster-detail',
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Clusters',
                link: '/clusters',
                target: 'id',
            },
        },
    },
    {
        path: '/targets/new',
        component: require('./pages/Target/New/index.vue').default,
        name: 'new-target',
        meta: {layout: 'default'},
    },
    {
        path: '/connection-types',
        component: require('./pages/Connection/Type/index.vue').default,
        name: 'connection-types',
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'base',
                name: 'Connection Types',
                link: '/connection-types',
            },
        },
    },
    {
        path: '/connection-types/:id',
        component: require('./pages/Connection/Type/_id.vue').default,
        name: 'connection-type-detail',
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Connection Types',
                link: '/connection-types',
                target: 'id',
            },
        },
    },
    {
        path: '/connection-types/new',
        component: require('./pages/Connection/Type/New/index.vue').default,
        name: 'new-connection-types',
        meta: {layout: 'default'},
    },
    {
        path: '/connection-groups',
        component: require('./pages/Connection/Group/index.vue').default,
        name: 'connection-groups',
        meta: {layout: 'default'},
    },
    {
        path: '/locations/add-village',
        component: require('./pages/Location/Village/New/index.vue').default,
        name: 'add-village',
        meta: {layout: 'default'},
    },
    {
        path: '/locations/add-mini-grid',
        component: require('./pages/Location/MiniGrid/New/index.vue').default,
        name: 'add-mini-grid',
        meta: {layout: 'default'},
    },
    {
        path: '/settings',
        component: require('./pages/Settings/index.vue').default,
        meta: {layout: 'default'},
    },
    {
        path: '/profile',
        component: require('./pages/Profile/index.vue').default,
        meta: {layout: 'default'},
    },
    {
        path: '/profile/management',
        component: require('./pages/Profile/Management/index.vue').default,
        meta: {layout: 'default'},
    },
    {
        path: '/agents/:id',
        component: require('./pages/Agent/_id.vue').default,
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'base',
                name: 'Agents',
                link: '/agents',
                target: 'id',
            },
        },
    },
    {
        path: '/sold-appliance-detail/:id',
        component: require('./pages/Client/Appliance/_id.vue').default,
        meta: {
            layout: 'default',
            breadcrumb: {
                level: 'detail',
                name: 'Sold Appliance Detail',
                link: '/sold-appliance-detail',
                target: 'id',
            },
        },
    },
    {
        path: '/kelin-meters/kelin-meter/status/:meter',
        component: require('./plugins/kelin-meter/js/modules/Meter/Status')
            .default,
        meta: {layout: 'default'},
    },
    {
        path: '/kelin-meters/kelin-meter/daily-consumptions/:meter',
        component:
        require('./plugins/kelin-meter/js/modules/Meter/Consumption/Daily')
            .default,
        meta: {layout: 'default'},
    },
    {
        path: '/kelin-meters/kelin-meter/minutely-consumptions/:meter',
        component:
        require('./plugins/kelin-meter/js/modules/Meter/Consumption/Minutely')
            .default,
        meta: {layout: 'default'},
    },
    {
        path: '/spark-meters/sm-tariff/:id',
        component:
        require('./plugins/spark-meter/js/modules/Tariff/TariffDetail')
            .default,
        meta: {layout: 'default'},
    },
    {
        path: '/steama-meters/steama-transaction/:customer_id',
        component:
        require('./plugins/steama-meter/js/modules/Customer/CustomerMovements')
            .default,
        meta: {layout: 'default'},
    },
    {
        path: '/stron-meters/stron-overview',
        component: require('./plugins/stron-meter/js/modules/Overview/Overview')
            .default,
        meta: {layout: 'default'},
    },
    {
        path: '/wave-money/payment/:name/:id',
        modules: {default: Payment, header: LoginHeader, footer: LoginFooter},
        name: '/wave-money/payment',
        props: {
            header: {colorOnScroll: 400},
        },
        meta: {requireAuth: false},
    },
    {
        path: '/wave-money/result/:name/:id',
        name: '/wave-money/result',
        modules: {default: Result, header: LoginHeader, footer: LoginFooter},
        props: {
            header: {colorOnScroll: 400},
        },
        meta: {requireAuth: false},
    },
]

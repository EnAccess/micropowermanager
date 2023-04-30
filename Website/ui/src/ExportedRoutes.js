import Login from './components/Login/Login'

import LoginHeader from './components/Login/LoginHeader'
import LoginFooter from './components/Login/LoginFooter'
import ForgotPassword from './components/Login/ForgotPassword'
import Welcome from './components/Welcome/Welcome'
import Register from './components/Register/Register'
import Payment from './plugins/wave-money-payment-provider/js/components/Payment/Payment'
import Result from './plugins/wave-money-payment-provider/js/components/Payment/Result'
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
        component: require('./components/ClustersDashboard/ClusterList').default,
        name: 'cluster-list-dashboard',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Clusters', link: '/' },
        },
    },
    {
        path: '/dashboards/mini-grid/:id',
        component: require('./components/MiniGrid/Dashboard').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Mini-Grid', link: '/dashboards/mini-grid', target: 'id' },
        },
    },
    {
        path: '/dashboards/mini-grid/',
        component: require('./components/MiniGrid/Selector').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Mini-Grids', link: '/dashboards/mini-grid' },
        },
    },
    {
        path: '/reports',
        component: require('./components/ExportedReports/ReportsList').default,
        meta: { layout: 'default' },
    },

    {
        path: '/people',
        component: require('./components/Client/ClientList').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Customers', link: '/people' },
        },
    },
    {
        path: '/people/:id',
        component: require('./components/Client/ClientDetail').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Customers', link: '/people', target: 'id' },
        },
    },
    {
        //transaction list
        path: '/transactions',
        component: require('./components/Transactions/TransactionList').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Transactions', link: '/transactions' },
        },
    },
    {
        //transaction list
        path: '/transactions/search',
        component: require('./components/Transactions/TransactionList').default,
        meta: { layout: 'default' },
    },
    {
        //transaction details
        path: '/transactions/:id',
        component: require('./components/Transactions/TransactionDetail').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Transactions', link: '/transactions', target: 'id' },
        },
    },
    {
        path: '/tickets',
        component: require('./components/Ticket/TicketList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/tickets/settings/users',
        component: require('./components/Ticket/UserManagement').default,
        meta: { layout: 'default' },
    },
    {
        path: '/tickets/settings/categories',
        component: require('./components/Ticket/LabelManagement').default,
        meta: { layout: 'default' },
    },
    {
        path: '/tariffs',
        component: require('./components/Tariff/TariffList').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Tariffs', link: '/tariffs' },
        },
    },
    {
        path: '/tariffs/:id',
        component: require('./components/Tariff/TariffDetail').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Tariffs', link: '/tariffs', target: 'id' },
        },
    },
    {
        path: '/meters',
        component: require('./components/Meter/MeterList').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Meters', link: '/meters' },
        },

    },
    {
        path: '/meters/types',
        component: require('./components/Meter/Types').default,
        meta: { layout: 'default' },

    },

    {
        path: '/meters/:id',
        component: require('./components/Meter/MeterDetail').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Meters', link: '/meters', target: 'id' },
        },

    },
    {
        path: '/user-management',
        component: require('./components/UserManagement/AddNewUser').default,
        meta: { layout: 'default' },

    },
    {
        path: '/clusters',
        component: require('./components/ClustersDashboard/ClusterList').default,
        name: 'cluster-list',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Clusters', link: '/clusters' }
        },
    },

    {
        path: '/locations/add-cluster',
        component: require('./components/ClustersDashboard/AddCluster').default,
        name: 'cluster-new',
        meta: { layout: 'default' },
    },

    {
        path: '/clusters/:id',
        component: require('./components/ClusterDashboard/ClusterDashboard').default,
        name: 'cluster-detail',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Clusters', link: '/clusters', target: 'id' }
        },
    },
    //targets
    {
        path: '/targets',
        component: require('./components/Target/TargetList').default,
        name: 'target-list',
        meta: { layout: 'default' },
    },
    {
        path: '/targets/new',
        component: require('./components/Target/NewTarget').default,
        name: 'new-target',
        meta: { layout: 'default' },
    },

    // connection-types
    {
        path: '/connection-types',
        component: require('./components/ConnectionTypes/ConnectionTypesList').default,
        name: 'connection-types',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Connection Types', link: '/connection-types' },
        },
    },
    // connection-types
    {
        path: '/connection-types/:id',
        component: require('./components/ConnectionTypes/ConnectionTypeDetail').default,
        name: 'connection-type-detail',
        meta: {
            layout: 'default', breadcrumb:
                { level: 'detail', name: 'Connection Types', link: '/connection-types', target: 'id' },
        }
    },
    {
        path: '/connection-types/new',
        component: require('./components/ConnectionTypes/NewConnectionType').default,
        name: 'new-connection-types',
        meta: { layout: 'default' },
    },
    // connection-types
    {
        path: '/connection-groups',
        component: require('./components/ConnectionGroups/ConnectionGroupsList').default,
        name: 'connection-groups',
        meta: { layout: 'default' },
    },
    {
        path: '/connection-types/new',
        component: require('./components/ConnectionGroups/NewConnectionGroup').default,
        name: 'new-connection-group',
        meta: { layout: 'default' },
    },
    {
        path: '/sms/list',
        component: require('./components/Sms/List').default,
        name: 'sms-list',
        meta: { layout: 'default' },
    },
    {
        path: '/sms/newsms',
        component: require('./components/Sms/NewSms').default,
        name: 'new-sms',
        meta: { layout: 'default' },
    },
    {
        path: '/maintenance',
        component: require('./components/Maintenance/Maintenance').default,
        name: 'maintenance',
        meta: { layout: 'default' },
    },
    {
        path: '/locations/add-village',
        component: require('./components/Village/AddVillage').default,
        name: 'add-village',
        meta: { layout: 'default' },
    },
    {
        path: '/locations/add-village/:id',
        component: require('./components/Village/AddVillage').default,
        name: 'add-village',
        meta: { layout: 'default' },
    },
    {
        path: '/locations/add-mini-grid',
        component: require('./components/MiniGrid/AddMiniGrid').default,
        name: 'add-mini-grid',
        meta: { layout: 'default' }
    },
    {
        path: '/assets/types',
        component: require('./components/Assets/AssetTypeList').default,
        name: 'asset-types',
        meta: { layout: 'default' },
    },

    {
        path: '/settings',
        component: require('./components/Settings/Settings').default,
        meta: { layout: 'default' },
    },
    {
        path: '/profile',
        component: require('./components/Profile/User').default,
        meta: { layout: 'default' },
    },
    {
        path: '/profile/management',
        component: require('./components/Profile/UserManagement').default,
        meta: { layout: 'default' },
    },
    {
        path: '/agents',
        component: require('./components/Agent/AgentList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/agents/:id',
        component: require('./components/Agent/Agent').default,
        meta: {
            layout: 'default', breadcrumb:
                { level: 'base', name: 'Agents', link: '/agents', target: 'id' },
        },
    },
    {
        path: '/commissions',
        component: require('./components/Agent/Commission/AgentCommissionList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/sell-appliance/:id',
        component: require('./components/Client/Appliances/SellAppliance.vue').default,
        meta: {
            layout: 'default', breadcrumb: {
                level: 'detail', name: 'Sell Appliance', link: '/sell-appliance/', target: 'id'
            }
        }
    },
    {
        path: '/sold-appliance-detail/:id',
        component: require('./components/Client/Appliances/SoldApplianceDetail.vue').default,
        meta: {
            layout: 'default', breadcrumb: {
                level: 'detail', name: 'Sold Appliance Detail', link: '/sold-appliance-detail', target: 'id'
            }
        }
    },
    {
        path: '/calin-meters/calin-overview',
        component: require('./plugins/calin-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/calin-smart-meters/calin-smart-overview',
        component: require('./plugins/calin-smart-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
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
    {
        path: '/steama-meters/steama-overview',
        component: require('./plugins/steama-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-site',
        component: require('./plugins/steama-meter/js/components/Site/SiteList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-customer',
        component: require('./plugins/steama-meter/js/components/Customer/CustomerList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-transaction/:customer_id',
        component: require('./plugins/steama-meter/js/components/Customer/CustomerMovements').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-meter',
        component: require('./plugins/steama-meter/js/components/Meter/MeterList').default,
        meta: { layout: 'default' },
    },

    {
        path: '/steama-meters/steama-agent',
        component: require('./plugins/steama-meter/js/components/Agent/AgentList').default,
        meta: { layout: 'default' },
    },
    {
        path: '/steama-meters/steama-setting',
        component: require('./plugins/steama-meter/js/components/Setting/Setting').default,
        meta: { layout: 'default' },
    },
    {
        path: '/stron-meters/stron-overview',
        component: require('./plugins/stron-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/bulk-registration/bulk-registration',
        component: require('./plugins/bulk-registration/js/components/Csv').default,
        meta: { layout: 'default' },
    },
    {
        path: '/viber-messaging/viber-overview',
        component: require('./plugins/viber-messaging/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/wave-money/wave-money-overview',
        component: require('./plugins/wave-money-payment-provider/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/wave-money/payment/:name/:id',
        components: { default: Payment, header: LoginHeader, footer: LoginFooter },
        name: '/wave-money/payment',
        props: {
            header: { colorOnScroll: 400 }
        },
        meta: { requireAuth: false }
    },
    {
        path: '/wave-money/result/:name/:id',
        name: '/wave-money/result',
        components: { default: Result, header: LoginHeader, footer: LoginFooter },
        props: {
            header: { colorOnScroll: 400 }
        },
        meta: { requireAuth: false }
    },
    {
        path: '/micro-star-meters/micro-star-overview',
        component: require('./plugins/micro-star-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/swifta-payment/swifta-payment-overview',
        component: require('./plugins/swifta-payment-provider/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/sun-king-meters/sun-king-overview',
        component: require('./plugins/sun-king-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
    {
        path: '/gome-long-meters/gome-long-overview',
        component: require('./plugins/gome-long-meter/js/components/Overview/Overview').default,
        meta: { layout: 'default' },
    },
        {
        path: '/wavecom/transactions',
        component: require('@/plugins/wavecom-payment-provider/js/components/Component.vue').default,
        meta: { layout: 'default' },
    },
]

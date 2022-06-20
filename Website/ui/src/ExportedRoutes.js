import Login from './components/Login/Login'

import LoginHeader from './components/Login/LoginHeader'
import LoginFooter from './components/Login/LoginFooter'
import ForgotPassword from './components/Login/ForgotPassword'
import Welcome from './components/Welcome/Welcome'

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

    path: '/forgot-password',
    name: 'forgot-password',
    components: { default: ForgotPassword, header: LoginHeader, footer: LoginFooter },

    meta: { requireAuth: false }
  },
  {
    path: '/',
    component: require('./components/ClustersDashboard/ClusterList').default,
    name: 'cluster-list-dashboard',
    meta: { layout: 'default', breadcrumb:
            { level: 'base', name: 'Clusters', link: '/' },
         },
  },
  {
    path: '/dashboards/mini-grid/:id',
    component: require('./components/MiniGrid/Dashboard').default,
    meta: { layout: 'default', breadcrumb:
            { level: 'detail', name: 'Mini-Grid', link: '/dashboards/mini-grid', target: 'id' },
         },
  },
  {
    path: '/dashboards/mini-grid/',
    component: require('./components/MiniGrid/Selector').default,
    meta: { layout: 'default', breadcrumb:
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
    meta: { layout: 'default', breadcrumb:
            { level: 'base', name: 'Customers', link: '/people' },
         },
  },
  {
    path: '/people/:id',
    component: require('./components/Client/ClientDetail').default,
    meta: { layout: 'default' , breadcrumb:
            { level: 'detail', name: 'Customers', link: '/people', target: 'id' },
         },
  },
  {
    //transaction list
    path: '/transactions',
    component: require('./components/Transactions/TransactionList').default,
    meta: { layout: 'default', breadcrumb:
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
    meta: { layout: 'default', breadcrumb:
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
    meta: { layout: 'default', breadcrumb:
            { level: 'base', name: 'Tariffs', link: '/tariffs' },
         },
  },
  {
    path: '/tariffs/:id',
    component: require('./components/Tariff/TariffDetail').default,
    meta: { layout: 'default' , breadcrumb:
            { level: 'detail', name: 'Tariffs', link: '/tariffs',target: 'id' },
        },
  },
  {
    path: '/meters',
    component: require('./components/Meter/MeterList').default,
    meta: { layout: 'default', breadcrumb:
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
    meta: { layout: 'default', breadcrumb:
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
    meta: { layout: 'default', breadcrumb:
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
    meta: { layout: 'default', breadcrumb:
            { level: 'detail', name: 'Clusters', link: '/clusters', target:'id' }
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
    meta: { layout: 'default', breadcrumb:
            { level: 'base', name: 'Connection Types', link: '/connection-types' },
         },
  },
  // connection-types
  {
    path: '/connection-types/:id',
    component: require('./components/ConnectionTypes/ConnectionTypeDetail').default,
    name: 'connection-type-detail',
    meta: { layout: 'default' , breadcrumb:
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
    meta: { layout: 'default', breadcrumb:
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
        level: 'detail', name: 'Sell Appliance', link: '/sell-appliance/', target: 'id' }
          }
  },
  {
    path: '/sold-appliance-detail/:id',
    component: require('./components/Client/Appliances/SoldApplianceDetail.vue').default,
    meta: {
        layout: 'default', breadcrumb: {
        level: 'detail', name: 'Sold Appliance Detail', link: '/sold-appliance-detail', target: 'id' }
          }
  },
]

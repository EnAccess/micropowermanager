import LoginHeader from "@/modules/Login/LoginHeader"
import LoginFooter from "@/modules/Login/LoginFooter"
import Welcome from "./pages/Welcome/index.vue"
import Login from "./pages/Login/index.vue"
import Register from "./pages/Register/index.vue"
import ForgotPassword from "./pages/ForgotPassword/index.vue"

import ChildRouteWrapper from "./shared/ChildRouteWrapper.vue"

import ClusterOverviewPage from "./pages/Dashboard/index.vue"
import ClusterDetailPage from "./pages/Dashboard/Cluster/_id.vue"
// MiniGridOverviewPage just redirects to MiniGridDetailPage for first Mini-Grid
import MiniGridOverviewPage from "./pages/Dashboard/MiniGrid/index.vue"
import MiniGridDetailPage from "./pages/Dashboard/MiniGrid/_id.vue"
import AddCluster from "./pages/Location/Cluster/New/index.vue"
import AddVillage from "./pages/Location/Village/New/index.vue"
import AddMiniGrid from "./pages/Location/MiniGrid/New/index.vue"
import SettingsPage from "./pages/Settings/Configuration/index.vue"
import ProfilePage from "./pages/Profile/index.vue"
import UserManagementPage from "./pages/Settings/UserManagement/index.vue"
import Reports from "./pages/Report/index.vue"
// FIXME: https://github.com/EnAccess/micropowermanager/issues/143
import CustomerList from "./pages/Client/index.vue"
import CustomerDetail from "./pages/Client/_id.vue"
import TransactionList from "./pages/Transaction/index.vue"
import TransactionSearch from "./pages/Transaction/index.vue"
import TransactionDetail from "./pages/Transaction/_id.vue"
import TicketList from "./pages/Ticket/index.vue"
import TariffList from "./pages/Tariff/index.vue"
import TariffDetail from "./pages/Tariff/_id.vue"
import MeterList from "./pages/Meter/index.vue"
import MeterDetail from "./pages/Meter/_id.vue"
import MeterTypeList from "./pages/MeterType/index.vue"
import SolarHomeSystem from "./pages/SolarHomeSystem/index.vue"
import SolarHomeSystemDetail from "./pages/SolarHomeSystem/_id.vue"
import TargetList from "./pages/Target/index.vue"
import TargetNew from "./pages/Target/New/index.vue"
import ConnectionTypeList from "./pages/Connection/Type/index.vue"
import ConnectionTypeDetail from "./pages/Connection/Type/_id.vue"
import ConnectionTypeNew from "./pages/Connection/Type/New/index.vue"
import ConnectionGroupList from "./pages/Connection/Group/index.vue"
import MessageList from "./pages/Sms/index.vue"
import MessageNew from "./pages/Sms/New/index.vue"
import Maintenance from "./pages/Maintenance/index.vue"
import ApplianceList from "./pages/Appliance/index.vue"
import AgentList from "./pages/Agent/index.vue"
import AgentCommissionTypeList from "./pages/Agent/Commission/index.vue"
import AgentDetail from "./pages/Agent/_id.vue"
import SoldApplianceDetail from "./pages/Client/Appliance/_id.vue"

// Former plugins
import CalinMeterOverview from "./plugins/calin-meter/js/modules/Overview/Overview"
import CalinSmartMeterOverview from "./plugins/calin-smart-meter/js/modules/Overview/Overview"
import KelinMeterOverview from "./plugins/kelin-meter/js/modules/Overview/Overview"
import KelinMeterCustomerList from "./plugins/kelin-meter/js/modules/Customer/List"
import KelinMeterSettings from "./plugins/kelin-meter/js/modules/Setting/Setting"
import KelinMeterList from "./plugins/kelin-meter/js/modules/Meter/List"
import KelinMeterStatus from "./plugins/kelin-meter/js/modules/Meter/Status"
import KelinMeterConsumptionDaily from "./plugins/kelin-meter/js/modules/Meter/Consumption/Daily"
import KelinMeterConsumptionMinutely from "./plugins/kelin-meter/js/modules/Meter/Consumption/Minutely"
import SparkMeterSiteList from "./plugins/spark-meter/js/modules/Site/SiteList"
import SparkMeterModelList from "./plugins/spark-meter/js/modules/MeterModel/MeterModelList"
import SparkMeterCustomerList from "./plugins/spark-meter/js/modules/Customer/CustomerList"
import SparkMeterTariffList from "./plugins/spark-meter/js/modules/Tariff/TariffList"
import SparkMeterTariffDetail from "./plugins/spark-meter/js/modules/Tariff/TariffDetail"
import SparkMeterOverview from "./plugins/spark-meter/js/modules/Overview/Overview"
import SparkMeterSalesAccountList from "./plugins/spark-meter/js/modules/SalesAccount/SalesAccountList"
import SparkMeterSettings from "./plugins/spark-meter/js/modules/Setting/Setting"
import SteamaCoOverview from "./plugins/steama-meter/js/modules/Overview/Overview"
import SteamaCoSiteList from "./plugins/steama-meter/js/modules/Site/SiteList"
import SteamaCoCustomerList from "./plugins/steama-meter/js/modules/Customer/CustomerList"
import SteamaCoCustomerDetail from "./plugins/steama-meter/js/modules/Customer/CustomerMovements"
import SteamaCoMeterList from "./plugins/steama-meter/js/modules/Meter/MeterList"
import SteamaCoAgentList from "./plugins/steama-meter/js/modules/Agent/AgentList"
import SteamaCoSettings from "./plugins/steama-meter/js/modules/Setting/Setting"
import StronMeterOverview from "./plugins/stron-meter/js/modules/Overview/Overview"
import BulkRegistrationCsv from "./plugins/bulk-registration/js/modules/Csv"
import ViberMessagingOverview from "./plugins/viber-messaging/js/modules/Overview/Overview"
import AfricasTalkingOverview from "./plugins/africas-talking/js/modules/Overview/Overview"
import WaveMoneyOverview from "./plugins/wave-money-payment-provider/js/modules/Overview/Overview"
// FIXME: These are used as modules which seem broken.
// https://github.com/EnAccess/micropowermanager/issues/142
import WaveMoneyResult from "./plugins/wave-money-payment-provider/js/modules/Payment/Result"
import WaveMoneyPayment from "./plugins/wave-money-payment-provider/js/modules/Payment/Payment"
import MicroStarMeterOverview from "./plugins/micro-star-meter/js/modules/Overview/Overview"
import SwiftaOverview from "./plugins/swifta-payment-provider/js/modules/Overview/Overview"
import SunKingShsOverview from "./plugins/sun-king-shs/js/modules/Overview/Overview"
import GomeLongOverview from "./plugins/gome-long-meter/js/modules/Overview/Overview"
import WavecomTransactionUpload from "./plugins/wavecom-payment-provider/js/modules/Component.vue"
// FIXME: Move e-Bikes higher, it's not a Plugin
import EBikeList from "./pages/EBikes/index.vue"
import DalyBmsOverview from "./plugins/daly-bms/js/modules/Overview/Overview"
import AngazaShsOverview from "./plugins/angaza-shs/js/modules/Overview/Overview"
import ChintMeterOverview from "./plugins/chint-meter/js/modules/Overview/Overview"

export const exportedRoutes = [
  // Welcome and login routes
  {
    path: "/welcome",
    name: "welcome",
    components: {
      default: Welcome,
      header: LoginHeader,
      footer: LoginFooter,
    },
  },
  {
    path: "/login",
    name: "login",
    components: {
      default: Login,
      header: LoginHeader,
      footer: LoginFooter,
    },
  },
  {
    path: "/register",
    name: "register",
    components: {
      default: Register,
      header: LoginHeader,
      footer: LoginFooter,
    },
  },
  {
    path: "/forgot-password",
    name: "forgot-password",
    components: {
      default: ForgotPassword,
      header: LoginHeader,
      footer: LoginFooter,
    },
  },
  // Top bar routes
  {
    // TBD: root route currently shows nothing
    // Should we add a redirect here?
    path: "/locations",
    component: ChildRouteWrapper,
    children: [
      {
        path: "add-cluster",
        component: AddCluster,
        name: "cluster-new",
        meta: {
          layout: "default",
        },
      },
      {
        path: "add-village",
        component: AddVillage,
        name: "add-village",
        meta: {
          layout: "default",
        },
      },
      {
        path: "add-mini-grid",
        component: AddMiniGrid,
        name: "add-mini-grid",
        meta: {
          layout: "default",
        },
      },
    ],
  },
  {
    path: "/settings",
    redirect: "/settings/configuration",
    component: ChildRouteWrapper,
    children: [
      {
        path: "configuration",
        component: SettingsPage,
        meta: {
          layout: "default",
        },
      },
      {
        path: "user-management",
        component: UserManagementPage,
        meta: {
          layout: "default",
        },
      },
      {
        path: "connection-groups",
        component: ConnectionGroupList,
        name: "connection-groups",
        meta: {
          layout: "default",
        },
      },
      {
        path: "connection-types",
        component: ChildRouteWrapper,
        children: [
          {
            path: "",
            component: ChildRouteWrapper,
            children: [
              {
                path: "",
                component: ConnectionTypeList,
                name: "connection-types",
                meta: {
                  layout: "default",
                  breadcrumb: {
                    level: "base",
                    name: "Connection Types",
                    link: "/settings/connection-types",
                  },
                },
              },
              {
                path: ":id",
                component: ConnectionTypeDetail,
                name: "connection-type-detail",
                meta: {
                  layout: "default",
                  breadcrumb: {
                    level: "detail",
                    name: "Connection Types",
                    link: "/settings/connection-types",
                    target: "id",
                  },
                },
              },
            ],
          },
          {
            path: "new",
            component: ConnectionTypeNew,
            name: "new-connection-types",
            meta: {
              layout: "default",
            },
          },
        ],
      },
    ],
  },
  {
    path: "/profile",
    component: ProfilePage,
    meta: {
      layout: "default",
    },
  },
  // Sidebar routes
  {
    path: "/",
    redirect: "/clusters",
    name: "cluster-list-dashboard",
    meta: {
      layout: "default",
      breadcrumb: {
        level: "base",
        name: "Clusters",
        link: "/",
      },
    },
  },
  {
    path: "",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Dashboards",
        icon: "home",
      },
    },
    children: [
      {
        path: "clusters",
        component: ChildRouteWrapper,
        meta: {
          sidebar: {
            enabled: true,
            name: "Clusters",
          },
        },
        children: [
          {
            path: "",
            component: ClusterOverviewPage,
            name: "cluster-list",
            meta: {
              layout: "default",
              breadcrumb: {
                level: "base",
                name: "Clusters",
                link: "/clusters",
              },
            },
          },
          {
            path: ":id",
            component: ClusterDetailPage,
            name: "cluster-detail",
            meta: {
              layout: "default",
              breadcrumb: {
                level: "detail",
                name: "Clusters",
                link: "/clusters",
                target: "id",
              },
            },
          },
        ],
      },
      {
        path: "dashboards/mini-grid",
        component: ChildRouteWrapper,
        meta: {
          sidebar: {
            enabled: true,
            name: "Mini-Grid",
          },
        },
        children: [
          {
            path: "",
            component: MiniGridOverviewPage,
            meta: {
              layout: "default",
              breadcrumb: {
                level: "base",
                name: "Mini-Grids",
                link: "/dashboards/mini-grid",
              },
              sidebar: {
                enabled: true,
                name: "Mini-Grid",
              },
            },
          },
          {
            path: ":id",
            component: MiniGridDetailPage,
            meta: {
              layout: "default",
              breadcrumb: {
                level: "detail",
                name: "Mini-Grid",
                link: "/dashboards/mini-grid",
                target: "id",
              },
            },
          },
        ],
      },
    ],
  },
  {
    path: "/reports",
    component: Reports,
    meta: {
      layout: "default",
      sidebar: {
        enabled: true,
        name: "Reports",
        icon: "text_snippet",
      },
    },
  },
  {
    path: "/people",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Customers",
        icon: "supervisor_account",
      },
    },
    children: [
      {
        path: "",
        component: CustomerList,
        meta: {
          layout: "default",
          breadcrumb: {
            level: "base",
            name: "Customers",
            link: "/people",
          },
        },
      },
      {
        path: ":id",
        component: CustomerDetail,
        meta: {
          layout: "default",
          breadcrumb: {
            level: "detail",
            name: "Customers",
            link: "/people",
            target: "id",
          },
        },
      },
    ],
  },
  {
    path: "/transactions",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Transactions",
        icon: "account_balance",
      },
    },
    children: [
      {
        path: "",
        component: TransactionList,
        meta: {
          layout: "default",
          breadcrumb: {
            level: "base",
            name: "Transactions",
            link: "/transactions",
          },
        },
      },
      {
        // transaction list
        // TODO: Why is this here? Doesn't seem to be used
        path: "search",
        component: TransactionSearch,
        meta: {
          layout: "default",
        },
      },
      {
        path: ":id",
        component: TransactionDetail,
        meta: {
          layout: "default",
          breadcrumb: {
            level: "detail",
            name: "Transactions",
            link: "/transactions",
            target: "id",
          },
        },
      },
    ],
  },
  {
    path: "",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Tickets",
        icon: "confirmation_number",
      },
    },
    children: [
      {
        path: "tickets",
        component: TicketList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "List",
          },
        },
      },
    ],
  },
  {
    path: "/tariffs",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Tariffs",
        icon: "widgets",
      },
    },
    children: [
      {
        path: "",
        component: TariffList,
        meta: {
          layout: "default",
          breadcrumb: {
            level: "base",
            name: "Tariffs",
            link: "/tariffs",
          },
        },
      },
      {
        path: ":id",
        component: TariffDetail,
        meta: {
          layout: "default",
          breadcrumb: {
            level: "detail",
            name: "Tariffs",
            link: "/tariffs",
            target: "id",
          },
        },
      },
    ],
  },
  {
    path: "",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Meters",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "meters",
        component: ChildRouteWrapper,
        meta: {
          sidebar: {
            enabled: true,
            name: "List",
          },
        },
        children: [
          {
            path: "",
            component: MeterList,
            meta: {
              layout: "default",
              breadcrumb: {
                level: "base",
                name: "Meters",
                link: "/meters",
              },
            },
          },
          {
            path: ":id",
            component: MeterDetail,
            meta: {
              layout: "default",
              breadcrumb: {
                level: "detail",
                name: "Meters",
                link: "/meters",
                target: "id",
              },
            },
          },
        ],
      },
      {
        path: "meters-types",
        component: MeterTypeList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Types",
          },
        },
      },
    ],
  },
  {
    path: "/solar-home-systems",
    component: SolarHomeSystem,
    meta: {
      layout: "default",
      sidebar: {
        enabled: true,
        name: "Solar Home Systems",
        icon: "solar_power",
      },
    },
  },
  {
    path: "/solar-home-systems/:id",
    component: SolarHomeSystemDetail,
    meta: {
      layout: "default",
      breadcrumb: {
        level: "detail",
        name: "Solar Home Systems",
        link: "/solar-home-systems",
        target: "id",
      },
    },
  },
  {
    path: "/targets",
    component: ChildRouteWrapper,
    meta: {
      layout: "default",
      sidebar: {
        enabled: true,
        name: "Targets",
        icon: "gps_fixed",
      },
    },
    children: [
      {
        path: "",
        component: TargetList,
        name: "target-list",
        meta: {
          layout: "default",
        },
      },
      {
        path: "new",
        component: TargetNew,
        name: "new-target",
        meta: {
          layout: "default",
        },
      },
    ],
  },
  {
    // TBD: root route currently shows nothing
    // Should we add a redirect here?
    path: "/sms",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Messages",
        icon: "sms",
      },
    },
    children: [
      //
      {
        path: "list",
        component: MessageList,
        name: "sms-list",
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Message List",
          },
        },
      },
      {
        path: "newsms",
        component: MessageNew,
        name: "new-sms",
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "New Message",
          },
        },
      },
    ],
  },
  {
    path: "/maintenance",
    component: Maintenance,
    name: "maintenance",
    meta: {
      layout: "default",
      sidebar: {
        enabled: true,
        name: "Maintenance",
        icon: "home_repair_service",
      },
    },
  },
  {
    path: "/assets",
    component: ApplianceList,
    name: "asset",
    meta: {
      layout: "default",
      sidebar: {
        enabled: true,
        name: "Appliances",
        icon: "devices_other",
      },
    },
  },
  {
    path: "",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled: true,
        name: "Agents",
        icon: "support_agent",
      },
    },
    children: [
      {
        path: "agents",
        component: ChildRouteWrapper,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "List",
          },
        },
        children: [
          {
            path: "",
            component: AgentList,
            meta: {
              layout: "default",
            },
          },
          {
            path: ":id",
            component: AgentDetail,
            meta: {
              layout: "default",
              breadcrumb: {
                level: "base",
                name: "Agents",
                link: "/agents",
                target: "id",
              },
            },
          },
        ],
      },
      {
        path: "commissions",
        component: AgentCommissionTypeList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Commission Types",
          },
        },
      },
    ],
  },
  {
    // FIXME: Should this be part of the Customer route?
    path: "/sold-appliance-detail",
    component: ChildRouteWrapper,
    children: [
      {
        path: ":id",
        component: SoldApplianceDetail,
        meta: {
          layout: "default",
          breadcrumb: {
            level: "detail",
            name: "Sold Appliance Detail",
            link: "/sold-appliance-detail",
            target: "id",
          },
        },
      },
    ],
  },
  {
    path: "/e-bikes",
    component: EBikeList,
    meta: {
      layout: "default",
      sidebar: {
        enabled: true,
        name: "E-Bikes",
        icon: "electric_bike",
      },
    },
  },
  /**
   *
   * PLUGIN ROUTES
   *
   */
  {
    path: "/spark-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 1,
        name: "Spark Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "sm-site",
        component: SparkMeterSiteList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Sites",
          },
        },
      },
      {
        path: "sm-meter-model",
        component: SparkMeterModelList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Meter Models",
          },
        },
      },
      {
        path: "sm-customer",
        component: SparkMeterCustomerList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Customers",
          },
        },
      },
      {
        path: "sm-tariff",
        component: ChildRouteWrapper,
        meta: {
          sidebar: {
            enabled: true,
            name: "Tariffs",
          },
        },
        children: [
          {
            path: "",
            component: SparkMeterTariffList,
            meta: {
              layout: "default",
            },
          },
          {
            path: ":id",
            component: SparkMeterTariffDetail,
            meta: {
              layout: "default",
            },
          },
        ],
      },
      {
        path: "sm-overview",
        component: SparkMeterOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
      {
        path: "sm-sales-account",
        component: SparkMeterSalesAccountList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Sales Accounts",
          },
        },
      },
      {
        path: "sm-setting",
        component: SparkMeterSettings,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Settings",
          },
        },
      },
    ],
  },
  {
    path: "/steama-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 2,
        name: "SteamaCo Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "steama-overview",
        component: SteamaCoOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
      {
        path: "steama-site",
        component: SteamaCoSiteList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Sites",
          },
        },
      },
      {
        path: "steama-customer",
        component: SteamaCoCustomerList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Customers",
          },
        },
      },
      {
        path: "steama-transaction/:customer_id",
        component: SteamaCoCustomerDetail,
        meta: {
          layout: "default",
        },
      },
      {
        path: "steama-meter",
        component: SteamaCoMeterList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Meters",
          },
        },
      },
      {
        path: "steama-agent",
        component: SteamaCoAgentList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Agents",
          },
        },
      },
      {
        path: "steama-setting",
        component: SteamaCoSettings,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Settings",
          },
        },
      },
    ],
  },
  {
    path: "/calin-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 3,
        name: "Calin Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "calin-overview",
        component: CalinMeterOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/calin-smart-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 4,
        name: "CalinSmart Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "calin-smart-overview",
        component: CalinSmartMeterOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/kelin-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 5,
        name: "Kelin Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "kelin-overview",
        component: KelinMeterOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
      {
        path: "kelin-customer",
        component: KelinMeterCustomerList,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Customers",
          },
        },
      },
      {
        path: "kelin-setting",
        component: KelinMeterSettings,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Settings",
          },
        },
      },
      {
        path: "kelin-meter",
        component: ChildRouteWrapper,
        meta: {
          sidebar: {
            enabled: true,
            name: "Meters",
          },
        },
        children: [
          {
            path: "",
            component: KelinMeterList,
            meta: {
              layout: "default",
            },
          },
          {
            path: "status/:meter",
            component: KelinMeterStatus,
            meta: {
              layout: "default",
            },
          },
          {
            path: "daily-consumptions/:meter",
            component: KelinMeterConsumptionDaily,
            meta: {
              layout: "default",
            },
          },
          {
            path: "minutely-consumptions/:meter",
            component: KelinMeterConsumptionMinutely,
            meta: {
              layout: "default",
            },
          },
        ],
      },
    ],
  },
  {
    path: "/stron-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 6,
        name: "Stron Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "stron-overview",
        component: StronMeterOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/swifta-payment",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 7,
        name: "Swifta",
        icon: "money",
      },
    },
    children: [
      {
        path: "swifta-payment-overview",
        component: SwiftaOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  // FIXME: Where is mpm_plugin_id = 8?
  // Seems to be a plugin called "MesombPayment"
  {
    path: "/bulk-registration/bulk-registration",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 9,
        name: "Bulk Registration",
        icon: "upload_file",
      },
    },
    children: [
      {
        path: "",
        component: BulkRegistrationCsv,
        meta: {
          layout: "default",
        },
      },
    ],
  },
  {
    path: "/viber-messaging",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 10,
        name: "Viber Messaging",
        icon: "message",
      },
    },
    children: [
      {
        path: "viber-overview",
        component: ViberMessagingOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/wave-money",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 11,
        name: "WaveMoney",
        icon: "money",
      },
    },
    children: [
      {
        path: "wave-money-overview",
        component: WaveMoneyOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
      {
        path: "payment/:name/:id",
        modules: {
          default: WaveMoneyPayment,
          header: LoginHeader,
          footer: LoginFooter,
        },
        name: "/wave-money/payment",
      },
      {
        path: "result/:name/:id",
        name: "/wave-money/result",
        modules: {
          default: WaveMoneyResult,
          header: LoginHeader,
          footer: LoginFooter,
        },
      },
    ],
  },
  {
    path: "/micro-star-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 12,
        name: "MicroStar Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "micro-star-overview",
        component: MicroStarMeterOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },

  {
    path: "/sun-king-shs",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 13,
        name: "SunKing SHS",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "sun-king-overview",
        component: SunKingShsOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/gome-long-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 14,
        name: "GomeLong Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "gome-long-overview",
        component: GomeLongOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/wavecom/transactions",
    component: WavecomTransactionUpload,
    meta: {
      layout: "default",
      sidebar: {
        enabled_by_mpm_plugin_id: 15,
        name: "Wavecom Payment Provider",
        icon: "upload_file",
      },
    },
  },
  {
    path: "/daly-bms",
    component: ChildRouteWrapper,
    meta: {
      layout: "default",
      sidebar: {
        enabled_by_mpm_plugin_id: 16,
        name: "Daly BMS",
        icon: "charging_station",
      },
    },
    children: [
      {
        path: "daly-bms-overview",
        component: DalyBmsOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/angaza-shs",
    component: ChildRouteWrapper,
    meta: {
      layout: "default",
      sidebar: {
        enabled_by_mpm_plugin_id: 17,
        name: "Angaza SHS",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "angaza-overview",
        component: AngazaShsOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/africas-talking",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 18,
        name: "Africas Talking",
        icon: "message",
      },
    },
    children: [
      {
        path: "africas-talking-overview",
        component: AfricasTalkingOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
  {
    path: "/chint-meters",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 20,
        name: "Chint Meter",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "chint-overview",
        component: ChintMeterOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
]

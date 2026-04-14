import MeterDetail from "./modules/Meter/MeterDetail.vue"
import MiniGridOverviewPage from "./modules/MiniGrid/MiniGridOverviewPage.vue"
import UserPasswordResetConfirm from "./modules/UserPasswordReset/UserPasswordResetConfirm.vue"
import ChildRouteWrapper from "./shared/ChildRouteWrapper.vue"

import AgentDetail from "@/modules/Agent/Agent.vue"
import AgentCommissionTypeList from "@/modules/Agent/Commission/AgentCommissionList.vue"
import ApplianceList from "@/modules/Appliances/Appliances.vue"
import SoldApplianceDetail from "@/modules/Client/Appliances/SoldApplianceDetail.vue"
import CustomerDetail from "@/modules/Client/Client.vue"
import CustomerList from "@/modules/Client/Clients.vue"
import AddCluster from "@/modules/Cluster/AddCluster.vue"
import ClusterDetailPage from "@/modules/Cluster/ClusterDetail.vue"
import ConnectionGroupList from "@/modules/ConnectionGroups/ConnectionGroupsList.vue"
import ConnectionTypeDetail from "@/modules/ConnectionTypes/ConnectionTypeDetail.vue"
import ConnectionTypeList from "@/modules/ConnectionTypes/ConnectionTypesList.vue"
import ConnectionTypeNew from "@/modules/ConnectionTypes/NewConnectionType.vue"
import AgentDashboardPage from "@/modules/Dashboard/AgentDashboard.vue"
import AgentPerformanceDashboardPage from "@/modules/Dashboard/AgentPerformanceDashboard.vue"
import ClusterOverviewPage from "@/modules/Dashboard/ClustersOverview.vue"
import EBikeList from "@/modules/EBikes/EBikes.vue"
import Reports from "@/modules/ExportedReports/Reports.vue"
import ForgotPassword from "@/modules/ForgotPassword/ForgotPassword.vue"
import Login from "@/modules/Login/Login.vue"
import LoginFooter from "@/modules/Login/LoginFooter.vue"
import LoginHeader from "@/modules/Login/LoginHeader.vue"
import Maintenance from "@/modules/Maintenance/Maintenance.vue"
import MeterList from "@/modules/Meter/Meters.vue"
import MeterTypeList from "@/modules/MeterType/MeterTypes.vue"
import AddMiniGrid from "@/modules/MiniGrid/AddMiniGrid.vue"
import MiniGridDetailPage from "@/modules/MiniGrid/Dashboard.vue"
import ProfilePage from "@/modules/Profile/Profile.vue"
import Register from "@/modules/Register/Register.vue"
import SettingsPage from "@/modules/Settings/Configuration/Configuration.vue"
import ImportPage from "@/modules/Settings/Configuration/ImportSettings.vue"
import UserManagementPage from "@/modules/Settings/UserManagement/UserManagement.vue"
import MessageList from "@/modules/Sms/List.vue"
import MessageNew from "@/modules/Sms/NewSms.vue"
import SolarHomeSystemDetail from "@/modules/SolarHomeSystem/SolarHomeSystemDetail.vue"
import SolarHomeSystem from "@/modules/SolarHomeSystem/SolarHomeSystems.vue"
import TargetNew from "@/modules/Target/NewTarget.vue"
import TargetList from "@/modules/Target/TargetList.vue"
import TariffDetail from "@/modules/Tariff/Tariff.vue"
import TariffList from "@/modules/Tariff/Tariffs.vue"
import TicketSettingsCategories from "@/modules/Ticket/CategoryManagement.vue"
import TicketList from "@/modules/Ticket/Tickets.vue"
import TicketSettingsUserManagement from "@/modules/Ticket/UserManagement.vue"
import TransactionDetail from "@/modules/Transactions/Transaction.vue"
import TransactionList from "@/modules/Transactions/Transactions.vue"
import UnauthorizedPage from "@/modules/Unauthorized/index.vue"
import AddVillage from "@/modules/Village/AddVillage.vue"
import Welcome from "@/modules/Welcome/index.vue"
import AfricasTalkingOverview from "@/plugins/africas-talking/modules/Overview/Overview.vue"
import AngazaShsOverview from "@/plugins/angaza-shs/modules/Overview/Overview.vue"
import BulkRegistrationCsv from "@/plugins/bulk-registration/modules/Csv.vue"
import CalinMeterOverview from "@/plugins/calin-meter/modules/Overview/Overview.vue"
import CalinSmartMeterOverview from "@/plugins/calin-smart-meter/modules/Overview/Overview.vue"
import ChintMeterOverview from "@/plugins/chint-meter/modules/Overview/Overview.vue"
import DalyBmsOverview from "@/plugins/daly-bms/modules/Overview/Overview.vue"
import EcreeeETenderOverview from "@/plugins/ecreee-e-tender/modules/Overview/Overview.vue"
import GomeLongOverview from "@/plugins/gome-long-meter/modules/Overview/Overview.vue"
import KelinMeterCustomerList from "@/plugins/kelin-meter/modules/Customer/List.vue"
import KelinMeterConsumptionDaily from "@/plugins/kelin-meter/modules/Meter/Consumption/Daily.vue"
import KelinMeterConsumptionMinutely from "@/plugins/kelin-meter/modules/Meter/Consumption/Minutely.vue"
import KelinMeterList from "@/plugins/kelin-meter/modules/Meter/List.vue"
import KelinMeterStatus from "@/plugins/kelin-meter/modules/Meter/Status.vue"
import KelinMeterOverview from "@/plugins/kelin-meter/modules/Overview/Overview.vue"
import KelinMeterSettings from "@/plugins/kelin-meter/modules/Setting/Setting.vue"
import MicroStarMeterOverview from "@/plugins/micro-star-meter/modules/Overview/Overview.vue"
import OdysseyExportOverview from "@/plugins/odyssey-data-export/modules/Overview/Overview.vue"
import PaystackCredential from "@/plugins/paystack-payment-provider/modules/Overview/Credential.vue"
import PaystackOverview from "@/plugins/paystack-payment-provider/modules/Overview/Overview.vue"
import PaystackPublicPayment from "@/plugins/paystack-payment-provider/modules/Payment/PublicPaymentForm.vue"
import PaystackPublicResult from "@/plugins/paystack-payment-provider/modules/Payment/PublicPaymentResult.vue"
import PaystackTransaction from "@/plugins/paystack-payment-provider/modules/Transaction/Transaction.vue"
import ProspectOverview from "@/plugins/prospect/modules/Overview/Overview.vue"
import ProspectSettings from "@/plugins/prospect/modules/Setting/Setting.vue"
import SparkMeterCustomerList from "@/plugins/spark-meter/modules/Customer/CustomerList.vue"
import SparkMeterModelList from "@/plugins/spark-meter/modules/MeterModel/MeterModelList.vue"
import SparkMeterOverview from "@/plugins/spark-meter/modules/Overview/Overview.vue"
import SparkMeterSalesAccountList from "@/plugins/spark-meter/modules/SalesAccount/SalesAccountList.vue"
import SparkMeterSettings from "@/plugins/spark-meter/modules/Setting/Setting.vue"
import SparkMeterSiteList from "@/plugins/spark-meter/modules/Site/SiteList.vue"
import SparkMeterTariffDetail from "@/plugins/spark-meter/modules/Tariff/TariffDetail.vue"
import SparkMeterTariffList from "@/plugins/spark-meter/modules/Tariff/TariffList.vue"
import SparkShsOverview from "@/plugins/spark-shs/modules/Overview/Overview.vue"
import SteamaCoAgentList from "@/plugins/steama-meter/modules/Agent/AgentList.vue"
import SteamaCoCustomerList from "@/plugins/steama-meter/modules/Customer/CustomerList.vue"
import SteamaCoCustomerDetail from "@/plugins/steama-meter/modules/Customer/CustomerMovements.vue"
import SteamaCoMeterList from "@/plugins/steama-meter/modules/Meter/MeterList.vue"
import SteamaCoOverview from "@/plugins/steama-meter/modules/Overview/Overview.vue"
import SteamaCoSettings from "@/plugins/steama-meter/modules/Setting/Setting.vue"
import SteamaCoSiteList from "@/plugins/steama-meter/modules/Site/SiteList.vue"
import StronMeterOverview from "@/plugins/stron-meter/modules/Overview/Overview.vue"
import SunKingShsOverview from "@/plugins/sun-king-shs/modules/Overview/Overview.vue"
import SwiftaOverview from "@/plugins/swifta-payment-provider/modules/Overview/Overview.vue"
import TextbeeSmsGatewayOverview from "@/plugins/textbee-sms-gateway/modules/Overview/Overview.vue"
import ViberMessagingOverview from "@/plugins/viber-messaging/modules/Overview/Overview.vue"
import WaveMoneyOverview from "@/plugins/wave-money-payment-provider/modules/Overview/Overview.vue"
import WaveMoneyPayment from "@/plugins/wave-money-payment-provider/modules/Payment/Payment.vue"
import WaveMoneyResult from "@/plugins/wave-money-payment-provider/modules/Payment/Result.vue"
import WavecomTransactionUpload from "@/plugins/wavecom-payment-provider/modules/Component.vue"

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
  {
    path: "/reset-password",
    name: "reset-password",
    components: {
      default: UserPasswordResetConfirm,
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
        path: "import",
        component: ImportPage,
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
  {
    path: "/unauthorized",
    component: UnauthorizedPage,
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
            // MiniGridOverviewPage just redirects to MiniGridDetailPage for first Mini-Grid
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

      {
        path: "dashboards/agent-performance",
        component: ChildRouteWrapper,
        meta: {
          sidebar: {
            enabled: true,
            name: "Agent Performance",
          },
        },
        children: [
          {
            path: "",
            component: AgentPerformanceDashboardPage,
            meta: {
              layout: "default",
              breadcrumb: {
                level: "base",
                name: "Agent Performance",
                link: "/dashboards/agent-performance",
              },
              sidebar: {
                enabled: true,
                name: "Agent Performance",
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
      {
        path: "tickets-settings/categories",
        component: TicketSettingsCategories,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Categories",
          },
        },
      },
      {
        path: "tickets-settings/user-management",
        component: TicketSettingsUserManagement,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "userManagement",
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
    path: "/appliances",
    component: ApplianceList,
    name: "appliance",
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
            name: "Overview",
          },
        },
        children: [
          {
            path: "",
            component: AgentDashboardPage,
            meta: {
              layout: "default",
              breadcrumb: {
                level: "base",
                name: "Agents",
                link: "/agents",
              },
              sidebar: {
                enabled: true,
                name: "Overview",
              },
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
      // FIXME: These are used as modules which seem broken.
      // https://github.com/EnAccess/micropowermanager/issues/142
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
    path: "/paystack/public",
    component: ChildRouteWrapper,
    children: [
      {
        path: "payment/:companyHash",
        component: PaystackPublicPayment,
        name: "/paystack/public/payment",
      },
      {
        path: "result/:companyHash",
        name: "/paystack/public/result",
        component: PaystackPublicResult,
      },
    ],
  },
  {
    path: "/paystack",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 25,
        name: "Paystack",
        icon: "payment",
      },
    },
    children: [
      {
        path: "overview",
        component: PaystackOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
      {
        path: "credential",
        component: PaystackCredential,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Credentials",
          },
        },
      },
      {
        path: "transactions",
        component: PaystackTransaction,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Transactions",
          },
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
    path: "/textbee-sms-gateway",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 26,
        name: "TextBee SMS Gateway",
        icon: "sms",
      },
    },
    children: [
      {
        path: "textbee-sms-gateway-overview",
        component: TextbeeSmsGatewayOverview,
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
  {
    path: "/prospect",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 24,
        name: "Prospect",
        icon: "bolt",
      },
    },
    children: [
      {
        path: "prospect-overview",
        component: ProspectOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
      {
        path: "prospect-setting",
        component: ProspectSettings,
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
    path: "/odyssey-data-export",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 23,
        name: "Odyssey Export",
        icon: "cloud_upload",
      },
    },
    children: [
      {
        path: "overview",
        component: OdysseyExportOverview,
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
    path: "/ecreee-e-tender",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 27,
        name: "Ecreee E Tender",
        icon: "cloud_upload",
      },
    },
    children: [
      {
        path: "overview",
        component: EcreeeETenderOverview,
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
    path: "/spark-shs",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 28,
        name: "Spark Shs",
        icon: "solar_power",
      },
    },
    children: [
      {
        path: "overview",
        component: SparkShsOverview,
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
  // NEW PLUGIN PLACEHOLDER (DO NOT REMOVE THIS LINE)
]

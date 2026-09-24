import Vue from "vue"
import VueRouter from "vue-router"

import PlatformOverview from "@/views/PlatformOverview.vue"
import TenantDetail from "@/views/TenantDetail.vue"
import Tenants from "@/views/Tenants.vue"

Vue.use(VueRouter)

export default new VueRouter({
  routes: [
    { path: "/", name: "overview", component: PlatformOverview },
    { path: "/tenants", name: "tenants", component: Tenants },
    {
      path: "/tenants/:tenantId",
      name: "tenant-detail",
      component: TenantDetail,
      props: true,
    },
    { path: "*", redirect: { name: "overview" } },
  ],
  linkActiveClass: "active",
  linkExactActiveClass: "exact-active",
  // The shell keeps the window as the scroll container, so this must not be
  // combined with an `overflow: auto` main element.
  scrollBehavior: () => ({ x: 0, y: 0 }),
})

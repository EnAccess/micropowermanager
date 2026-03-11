import VueRouter from "vue-router"

import { exportedRoutes } from "./ExportedRoutes.js"

let routes = exportedRoutes

export default new VueRouter({
  routes,
  linkActiveClass: "active",
  linkExactActiveClass: "exact-active",
})

const SidebarStore = {
  showSidebar: false,
  displaySidebar(value) {
    this.showSidebar = value
  },
}

const Sidebar = {
  install(Vue) {
    Vue.mixin({
      data() {
        return {
          sidebarStore: SidebarStore,
        }
      },
    })

    Object.defineProperty(Vue.prototype, "$sidebar", {
      get() {
        return this.$root.sidebarStore
      },
    })
    Vue.component("side-bar", Sidebar)
  },
}

export default Sidebar

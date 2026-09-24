<template>
  <div class="shell">
    <operator-sidebar :open="sidebarOpen" />
    <div
      v-if="sidebarOpen"
      class="shell__backdrop"
      @click="sidebarOpen = false"
    ></div>
    <!-- min-width: 0 keeps a wide table scrolling inside the column instead of
         stretching the flex row. -->
    <div class="shell__column">
      <operator-top-bar
        :sidebar-open="sidebarOpen"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />
      <main class="shell__main">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script>
import OperatorSidebar from "@/layouts/OperatorSidebar.vue"
import OperatorTopBar from "@/layouts/OperatorTopBar.vue"
import { notify } from "@/mixins/notify.js"

export default {
  name: "OperatorShell",
  components: { OperatorSidebar, OperatorTopBar },
  mixins: [notify],
  data() {
    return { sidebarOpen: false }
  },
  watch: {
    $route() {
      this.sidebarOpen = false
    },
  },
  mounted() {
    window.addEventListener("keydown", this.closeSidebarOnEscape)
  },
  beforeDestroy() {
    window.removeEventListener("keydown", this.closeSidebarOnEscape)
  },
  created() {
    // The sidebar count and the topbar's freshness stamp come from the platform
    // payload, which a deep link into a tenant would otherwise never load.
    if (this.$store.state.operatorDashboard.summary === null) {
      this.loadPlatform()
    }
  },
  methods: {
    closeSidebarOnEscape(event) {
      if (event.key === "Escape") {
        this.sidebarOpen = false
      }
    },
    async loadPlatform() {
      try {
        await this.$store.dispatch("operatorDashboard/fetchPlatform")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.shell {
  display: flex;
  min-height: 100vh;
  background: $ops-page-background;
}

.shell__column {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.shell__main {
  flex: 1;
  padding: 24px 24px 48px;
  min-width: 0;
}

.shell__backdrop {
  position: fixed;
  inset: 0;
  z-index: 25;
  background: rgba(0, 0, 0, 0.3);
}

@media screen and (max-width: $ops-breakpoint-phone) {
  .shell__main {
    padding: 16px 12px 32px;
  }
}
</style>

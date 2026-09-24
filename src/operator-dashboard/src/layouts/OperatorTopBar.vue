<template>
  <header class="topbar">
    <button
      type="button"
      class="mpm-button mpm-button--on-header topbar__menu"
      :aria-label="$tc(sidebarOpen ? 'phrases.closeMenu' : 'phrases.openMenu')"
      @click="$emit('toggle-sidebar')"
    >
      <span class="material-icons">
        {{ sidebarOpen ? "keyboard_arrow_left" : "menu" }}
      </span>
    </button>
    <div class="topbar__breadcrumb">
      <router-link
        v-if="isDetail"
        :to="{ name: 'tenants' }"
        class="topbar__crumb"
      >
        {{ $tc("words.tenants") }}
      </router-link>
      <span v-else class="topbar__crumb">
        {{ rootCrumb }}
      </span>
      <template v-if="isDetail">
        <span class="topbar__separator">&gt;</span>
        <span class="topbar__crumb">
          {{ detailName }}
        </span>
      </template>
    </div>

    <div class="topbar__actions">
      <span class="topbar__freshness">
        {{ $tc("phrases.dataAsOf", 1, { timestamp: formattedGeneratedAt }) }}
      </span>
      <button
        type="button"
        class="mpm-button mpm-button--raised mpm-button--nav mpm-button--dense"
        :class="{ 'topbar__refresh--busy': refreshing }"
        :disabled="refreshing"
        @click="refresh"
      >
        <span class="material-icons topbar__refresh-icon">update</span>
        <span class="topbar__refresh-label">
          {{
            refreshing ? $tc("phrases.refreshing") : $tc("phrases.refreshData")
          }}
        </span>
      </button>
    </div>

    <progress-bar v-if="refreshing" class="topbar__progress" indeterminate />
  </header>
</template>

<script>
import { formatDataAsOf } from "@/Helpers/format.js"
import { notify } from "@/mixins/notify.js"
import ProgressBar from "@/shared/ProgressBar.vue"

export default {
  name: "OperatorTopBar",
  components: { ProgressBar },
  mixins: [notify],
  props: {
    sidebarOpen: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    isDetail() {
      return this.$route.name === "tenant-detail"
    },
    rootCrumb() {
      return this.$route.name === "tenants"
        ? this.$tc("words.tenants")
        : this.$tc("phrases.platformOverview")
    },
    detailName() {
      const detail = this.$store.state.operatorDashboard.tenantDetail

      return detail ? detail.name : ""
    },
    refreshing() {
      return this.$store.state.operatorDashboard.refreshing
    },
    formattedGeneratedAt() {
      return formatDataAsOf(this.$store.state.operatorDashboard.generatedAt)
    },
  },
  methods: {
    async refresh() {
      try {
        await this.$store.dispatch("operatorDashboard/refresh")
      } catch (e) {
        this.alertNotify(
          "error",
          this.$tc(e.message || "phrases.refreshFailed"),
        )
      }
    },
  },
}
</script>

<style lang="scss" scoped>
// Mirrors src/frontend's TopNavbar and Breadcrumb.
.topbar {
  position: sticky;
  top: 0;
  z-index: 20;
  min-height: 64px;
  flex: none;
  background: $ops-shell-topbar;
  display: flex;
  align-items: center;
  padding: 0 16px;
}

.topbar__menu {
  display: none;
  margin-right: 8px;
  padding: 0 8px;
}

.topbar__breadcrumb {
  display: flex;
  align-items: center;
  min-width: 0;
  color: $brand-white;
  font-style: italic;
  font-weight: bold;
}

.topbar__crumb {
  color: $brand-white;
  text-decoration: underline;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  &:hover {
    color: $brand-white;
  }
}

.topbar__separator {
  margin: 0 0.5em;
  color: whitesmoke;
  font-size: 0.8em;
}

.topbar__actions {
  margin-left: auto;
  padding-left: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  flex: none;
}

.topbar__freshness {
  font-size: $font-caption;
  color: $ops-shell-text-muted;
}

// Mirrors the spinning refresh affordance in the tenant admin panel.
.topbar__refresh--busy .topbar__refresh-icon {
  animation: ops-rotate 1.4s linear infinite;
}

.topbar__progress {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
}

@media screen and (max-width: #{$ops-breakpoint-desktop - 1px}) {
  .topbar__menu {
    display: inline-flex;
  }
}

// On phones the actions shrink to icons, as src/frontend's mobile navbar does.
@media screen and (max-width: $ops-breakpoint-phone) {
  .topbar__freshness,
  .topbar__refresh-label {
    display: none;
  }
}
</style>

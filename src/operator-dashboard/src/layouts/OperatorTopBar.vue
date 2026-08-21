<template>
  <header class="topbar">
    <div class="topbar__breadcrumb">
      <router-link
        v-if="isDetail"
        :to="{ name: 'tenants' }"
        class="topbar__crumb topbar__crumb--link"
      >
        {{ $tc("words.tenants") }}
      </router-link>
      <span v-else class="topbar__crumb topbar__crumb--current">
        {{ rootCrumb }}
      </span>
      <template v-if="isDetail">
        <span class="topbar__separator">/</span>
        <span class="topbar__crumb topbar__crumb--current">
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
        class="topbar__button"
        :disabled="refreshing"
        :title="$tc('phrases.refreshData')"
        @click="refresh"
      >
        <span class="material-icons topbar__button-icon">update</span>
      </button>
      <div class="topbar__operator">
        <span class="material-icons topbar__operator-icon">person</span>
        <span>{{ $tc("words.operator") }}</span>
      </div>
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
.topbar {
  position: sticky;
  top: 0;
  z-index: 20;
  height: 52px;
  flex: none;
  background: $ops-shell-topbar;
  display: flex;
  align-items: center;
  padding: 0 24px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.topbar__breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 300;
}

.topbar__crumb--current {
  color: $brand-white;
}

.topbar__crumb--link {
  color: $ops-shell-text-muted;

  &:hover {
    color: $brand-white;
  }
}

.topbar__separator {
  color: $ops-shell-text-faint;
}

.topbar__actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 16px;
}

.topbar__freshness {
  font-size: 12px;
  color: $ops-shell-text-muted;
  font-weight: 300;
}

.topbar__button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: $ops-radius-control;
  background: $ops-shell-button;
  color: $brand-white;
  cursor: pointer;

  &:hover:not(:disabled) {
    background: $ops-shell-button-hover;
  }

  &:disabled {
    cursor: default;
    opacity: 0.6;
  }
}

.topbar__button-icon {
  font-size: 18px;
}

.topbar__operator {
  display: flex;
  align-items: center;
  gap: 6px;
  color: $ops-shell-text;
  font-size: 13px;
  font-weight: 300;
}

.topbar__operator-icon {
  font-size: 19px;
  color: $ops-shell-text-muted;
}

.topbar__progress {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
}
</style>

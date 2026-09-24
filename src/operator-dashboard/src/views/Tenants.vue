<template>
  <div>
    <spinner v-if="loading && tenants.length === 0" />

    <ops-card v-else :title="$tc('words.tenants')">
      <template #actions>
        <label class="mpm-field mpm-field--on-header tenants__search">
          <input
            v-model="query"
            class="mpm-field__input"
            type="search"
            :placeholder="$tc('phrases.searchTenants')"
          />
        </label>
        <button
          v-if="query"
          type="button"
          class="mpm-button mpm-button--on-header tenants__search-action"
          :aria-label="$tc('phrases.clearSearch')"
          @click="query = ''"
        >
          <span class="material-icons">cancel</span>
        </button>
        <span v-else class="material-icons tenants__search-action">search</span>
      </template>

      <div class="tenants__meta">
        <span>
          {{
            $tc("phrases.showingXofY", 1, {
              shown: filteredTenants.length,
              total: tenants.length,
            })
          }}
        </span>
        <div class="tenants__legend">
          <span
            v-for="status in statuses"
            :key="status"
            class="tenants__legend-item"
          >
            <status-dot :status="status" :size="8" />
            {{ $tc(`words.${status}`) }}
          </span>
        </div>
      </div>

      <tenants-table
        v-if="filteredTenants.length > 0"
        :tenants="filteredTenants"
      />
      <empty-state
        v-else
        icon="cancel"
        :description="$tc('phrases.noTenantsMatch')"
      />
    </ops-card>
  </div>
</template>

<script>
import { HEALTH_STATUSES } from "@/design/health.js"
import { notify } from "@/mixins/notify.js"
import EmptyState from "@/shared/EmptyState.vue"
import OpsCard from "@/shared/OpsCard.vue"
import Spinner from "@/shared/Spinner.vue"
import StatusDot from "@/shared/StatusDot.vue"
import TenantsTable from "@/views/parts/TenantsTable.vue"

export default {
  name: "Tenants",
  components: { EmptyState, OpsCard, Spinner, StatusDot, TenantsTable },
  mixins: [notify],
  data() {
    return {
      query: "",
      statuses: HEALTH_STATUSES,
    }
  },
  computed: {
    tenants() {
      return this.$store.state.operatorDashboard.tenants
    },
    loading() {
      return this.$store.state.operatorDashboard.loading.platform
    },
    filteredTenants() {
      const query = this.query.trim().toLowerCase()

      if (query === "") {
        return this.tenants
      }

      return this.tenants.filter(
        (tenant) =>
          tenant.name.toLowerCase().includes(query) ||
          (tenant.country || "").toLowerCase().includes(query),
      )
    },
  },
  created() {
    if (this.tenants.length === 0) {
      this.load()
    }
  },
  methods: {
    async load() {
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
.tenants__search {
  width: 320px;
  max-width: 100%;
  min-width: 0;
  flex: 1 1 auto;
}

.tenants__search-action {
  min-width: 0;
  padding: 0 4px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  font-size: 24px;
}

.tenants__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 8px 24px;
  font-size: $font-caption;
  color: $ops-text-muted;
}

.tenants__legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 14px;
}

.tenants__legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
}
</style>

<template>
  <div>
    <h1 class="page__title">{{ $tc("words.tenants") }}</h1>
    <p class="page__subtitle">{{ $tc("phrases.tenantsSubtitle") }}</p>

    <spinner v-if="loading && tenants.length === 0" />

    <ops-card v-else>
      <div class="tenants__toolbar">
        <div class="tenants__search">
          <span class="material-icons tenants__search-icon">search</span>
          <input
            v-model="query"
            class="tenants__search-input"
            type="search"
            :placeholder="$tc('phrases.searchTenants')"
          />
        </div>
        <div class="tenants__meta">
          <span class="tenants__count">
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
      </div>

      <tenants-table
        v-if="filteredTenants.length > 0"
        :tenants="filteredTenants"
      />
      <empty-state
        v-else
        icon="cancel"
        :description="$tc('phrases.noTenantsMatch')"
      >
        <button type="button" class="tenants__clear" @click="query = ''">
          {{ $tc("phrases.clearSearch") }}
        </button>
      </empty-state>
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
.page__title {
  margin: 0 0 2px;
  font-weight: 300;
  font-size: 25px;
  color: $brand-primary-dark;
  line-height: 1.3;
}

.page__subtitle {
  margin: 0 0 22px;
  color: $ops-text-muted;
  font-size: 13.5px;
  font-weight: 300;
}

.tenants__toolbar {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 20px;
  border-bottom: 1px solid $ops-card-border;
  flex-wrap: wrap;
}

.tenants__search {
  display: flex;
  align-items: center;
  gap: 9px;
  background: $brand-background;
  border: 1px solid $ops-card-border;
  border-radius: $ops-radius-control;
  padding: 8px 12px;
  flex: 1;
  min-width: 240px;
  max-width: 400px;
}

.tenants__search-icon {
  font-size: 18px;
  color: $ops-text-muted;
}

.tenants__search-input {
  border: none;
  background: transparent;
  outline: none;
  font-family: inherit;
  font-size: 13.5px;
  color: $ops-text;
  width: 100%;
}

.tenants__meta {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 18px;
}

.tenants__count {
  color: $ops-text-muted;
  font-size: 12.5px;
  font-weight: 300;
}

.tenants__legend {
  display: flex;
  align-items: center;
  gap: 14px;
  font-size: 12px;
  color: $ops-text-muted;
  font-weight: 300;
}

.tenants__legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.tenants__clear {
  border: 1px solid $ops-card-border;
  background: $brand-white;
  border-radius: $ops-radius-control;
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 500;
  color: $brand-primary;
  padding: 7px 14px;
  cursor: pointer;
}
</style>

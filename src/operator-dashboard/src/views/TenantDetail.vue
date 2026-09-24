<template>
  <div>
    <router-link :to="{ name: 'tenants' }" class="detail__back">
      <span class="material-icons detail__back-icon">arrow_back</span>
      {{ $tc("phrases.backToAllTenants") }}
    </router-link>

    <spinner v-if="loading && !tenant" />

    <template v-else-if="tenant">
      <header class="detail__header">
        <h1 class="detail__title">
          {{ tenant.name }}
          <span class="mpm-chip" :class="`status-chip--${tenant.health}`">
            {{ $tc(`words.${tenant.health}`) }}
          </span>
        </h1>
        <p class="detail__meta">{{ metaLine }}</p>
        <div v-if="tenant.plugins.length > 0" class="detail__plugins">
          <span
            v-for="plugin in tenant.plugins"
            :key="plugin"
            class="mpm-chip mpm-chip--primary"
          >
            {{ plugin }}
          </span>
        </div>
      </header>

      <kpi-strip :items="kpis" class="detail__kpis" />

      <div class="detail__grid">
        <tenant-transactions-card :monthly="tenant.monthly" />
        <tenant-fleet-health-card :tenant="tenant" />
      </div>

      <tenant-activity-card
        :activity="tenant.activity"
        class="detail__activity"
      />
    </template>
  </div>
</template>

<script>
import { formatCount, formatMoney, formatMonthYear } from "@/Helpers/format.js"
import { notify } from "@/mixins/notify.js"
import KpiStrip from "@/shared/KpiStrip.vue"
import Spinner from "@/shared/Spinner.vue"
import TenantActivityCard from "@/views/parts/TenantActivityCard.vue"
import TenantFleetHealthCard from "@/views/parts/TenantFleetHealthCard.vue"
import TenantTransactionsCard from "@/views/parts/TenantTransactionsCard.vue"

export default {
  name: "TenantDetail",
  components: {
    KpiStrip,
    Spinner,
    TenantActivityCard,
    TenantFleetHealthCard,
    TenantTransactionsCard,
  },
  mixins: [notify],
  props: {
    tenantId: {
      type: [String, Number],
      required: true,
    },
  },
  computed: {
    tenant() {
      return this.$store.state.operatorDashboard.tenantDetail
    },
    loading() {
      return this.$store.state.operatorDashboard.loading.detail
    },
    metaLine() {
      return [
        this.tenant.country,
        this.tenant.usageType,
        this.$tc("phrases.registeredIn", 1, {
          month: formatMonthYear(this.tenant.registeredAt),
        }),
        this.tenant.email,
        this.tenant.phone,
      ]
        .filter(Boolean)
        .join(" · ")
    },
    kpis() {
      return [
        {
          label: this.$tc("words.customers"),
          icon: "supervisor_account",
          color: "orange",
          value: formatCount(this.tenant.customers),
        },
        {
          label: this.$tc("words.devices"),
          icon: "settings_input_hdmi",
          color: "red",
          value: formatCount(this.tenant.devices.total),
        },
        {
          label: this.$tc("phrases.thisMonthTransactions"),
          icon: "swap_horiz",
          color: "blue",
          value: formatCount(this.tenant.transactionsThisMonth),
        },
        {
          // The only place money appears: one tenant, in its own currency.
          label: this.$tc("phrases.volumePerMonth"),
          icon: "attach_money",
          color: "green",
          value: formatMoney(this.tenant.volumeThisMonth, this.tenant.currency),
        },
      ]
    },
  },
  watch: {
    tenantId: "load",
  },
  created() {
    this.load()
  },
  methods: {
    async load() {
      try {
        await this.$store.dispatch(
          "operatorDashboard/fetchTenantDetail",
          this.tenantId,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.detail__back {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: $font-cell;
  font-weight: 400;
  color: $brand-primary;
  margin-bottom: 14px;
}

.detail__back-icon {
  font-size: 18px;
}

.detail__header {
  margin-bottom: 20px;
}

.detail__title {
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  font-size: $font-h3;
  line-height: 1.4em;
}

.detail__meta {
  margin: 4px 0 0;
  font-size: $font-cell;
  color: $ops-text-muted;
}

.detail__plugins {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}

.detail__kpis {
  margin-bottom: $ops-gap;
}

.detail__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(380px, 100%), 1fr));
  gap: $ops-gap;
  align-items: start;
  margin-bottom: $ops-gap;
}
</style>

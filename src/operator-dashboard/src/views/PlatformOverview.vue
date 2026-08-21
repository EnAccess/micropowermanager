<template>
  <div>
    <h1 class="page__title">{{ $tc("phrases.platformOverview") }}</h1>
    <p class="page__subtitle">{{ $tc("phrases.platformOverviewSubtitle") }}</p>

    <spinner v-if="loading && !summary" />

    <template v-else-if="summary">
      <kpi-strip :items="kpis" class="overview__kpis" />

      <transactions-hero-card :monthly="monthly" class="overview__hero" />

      <div class="overview__grid">
        <device-fleet-card :devices="summary.devicesTotal" />
        <needs-attention-card :tenants="attentionTenants" />
      </div>
    </template>
  </div>
</template>

<script>
import { formatCount, formatPercentage } from "@/Helpers/format.js"
import { notify } from "@/mixins/notify.js"
import KpiStrip from "@/shared/KpiStrip.vue"
import Spinner from "@/shared/Spinner.vue"
import DeviceFleetCard from "@/views/parts/DeviceFleetCard.vue"
import NeedsAttentionCard from "@/views/parts/NeedsAttentionCard.vue"
import TransactionsHeroCard from "@/views/parts/TransactionsHeroCard.vue"

export default {
  name: "PlatformOverview",
  components: {
    DeviceFleetCard,
    KpiStrip,
    NeedsAttentionCard,
    Spinner,
    TransactionsHeroCard,
  },
  mixins: [notify],
  computed: {
    summary() {
      return this.$store.state.operatorDashboard.summary
    },
    monthly() {
      return this.$store.state.operatorDashboard.monthly
    },
    loading() {
      return this.$store.state.operatorDashboard.loading.platform
    },
    attentionTenants() {
      return this.$store.getters["operatorDashboard/attentionTenants"]
    },
    kpis() {
      const summary = this.summary

      return [
        {
          label: this.$tc("words.tenants"),
          value: formatCount(summary.tenantsTotal),
          hint:
            summary.tenantsNewThisMonth > 0
              ? this.$tc("phrases.newThisMonth", 1, {
                  value: summary.tenantsNewThisMonth,
                })
              : null,
          tone: "positive",
        },
        {
          label: this.$tc("phrases.tenantsActive"),
          value: formatCount(summary.tenantsActive),
          hint: this.$tc("phrases.transacting", 1, {
            percentage: formatPercentage(summary.tenantsActivePercentage),
          }),
          tone: "neutral",
        },
        {
          label: this.$tc("phrases.thisMonthTransactions"),
          value: formatCount(summary.transactionsThisMonth),
          hint:
            summary.transactionsTrendPercentage === null
              ? null
              : this.$tc("phrases.vsLastMonth", 1, {
                  percentage: formatPercentage(
                    summary.transactionsTrendPercentage,
                    {
                      withSign: true,
                    },
                  ),
                }),
          tone:
            summary.transactionsTrendPercentage < 0 ? "negative" : "positive",
        },
        {
          label: this.$tc("phrases.devicesDeployed"),
          value: formatCount(summary.devicesTotal.total),
          hint: this.$tc("phrases.customersServed", 1, {
            value: formatCount(summary.customersTotal),
          }),
          tone: "neutral",
        },
      ]
    },
  },
  created() {
    if (this.summary === null) {
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

.overview__kpis,
.overview__hero {
  margin-bottom: $ops-gap;
}

.overview__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
  gap: $ops-gap;
  align-items: start;
}
</style>

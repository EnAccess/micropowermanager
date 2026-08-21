<template>
  <ops-card
    :title="$tc('phrases.transactionsPerMonth')"
    :subtitle="$tc('phrases.transactionsPerMonthSubtitle')"
    class="hero"
  >
    <template #actions>
      <div class="hero__toggle">
        <button
          type="button"
          class="hero__segment"
          :class="{ 'hero__segment--active': mode === 'count' }"
          @click="mode = 'count'"
        >
          {{ $tc("phrases.count") }}
        </button>
        <button
          type="button"
          class="hero__segment"
          :class="{ 'hero__segment--active': mode === 'provider' }"
          @click="mode = 'provider'"
        >
          {{ $tc("phrases.byProvider") }}
        </button>
      </div>
    </template>

    <div class="hero__chart">
      <v-chart
        v-if="hasData"
        :option="option"
        :autoresize="true"
        class="hero__canvas"
      />
      <empty-state
        v-else
        icon="show_chart"
        :description="$tc('phrases.noTenantsMatch')"
      />
    </div>
  </ops-card>
</template>

<script>
import {
  heroCountOption,
  heroProviderOption,
} from "@/charts/transactionOptions.js"
import {
  formatCount,
  formatPeriodLabel,
  formatProviderLabel,
} from "@/Helpers/format.js"
import EmptyState from "@/shared/EmptyState.vue"
import OpsCard from "@/shared/OpsCard.vue"

export default {
  name: "TransactionsHeroCard",
  components: { EmptyState, OpsCard },
  props: {
    monthly: {
      type: Object,
      required: true,
    },
  },
  data() {
    return { mode: "count" }
  },
  computed: {
    labels() {
      return this.monthly.periods.map(formatPeriodLabel)
    },
    hasData() {
      return this.monthly.periods.length > 0
    },
    providerSeries() {
      return Object.entries(this.monthly.byProvider || {}).map(
        ([alias, counts]) => ({
          name: formatProviderLabel(alias),
          counts,
        }),
      )
    },
    option() {
      const shared = {
        labels: this.labels,
        formatValue: (value) =>
          this.$tc("phrases.transactionCount", 1, {
            value: formatCount(value),
          }),
        formatAxisValue: (value) =>
          value >= 1000 ? `${value / 1000}k` : value,
      }

      if (this.mode === "provider" && this.providerSeries.length > 0) {
        return heroProviderOption({ ...shared, series: this.providerSeries })
      }

      return heroCountOption({ ...shared, counts: this.monthly.transactions })
    },
  },
}
</script>

<style lang="scss" scoped>
.hero {
  padding-bottom: 10px;
}

.hero__toggle {
  display: flex;
  background: $brand-background;
  border: 1px solid $ops-card-border;
  border-radius: $ops-radius-control;
  padding: 2px;
  gap: 2px;
}

.hero__segment {
  border: none;
  background: transparent;
  color: $ops-text-muted;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  padding: 6px 14px;
  border-radius: $ops-radius-control;
  cursor: pointer;
}

.hero__segment--active {
  background: $brand-primary;
  color: $brand-white;
}

.hero__chart {
  padding: 0 24px 10px;
}

.hero__canvas {
  width: 100%;
  height: 300px;
}
</style>

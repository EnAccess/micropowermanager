<template>
  <ops-card
    :title="$tc('phrases.transactionsPerMonth')"
    :subtitle="$tc('phrases.transactionsPerMonthSubtitle')"
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
.hero__toggle {
  display: flex;
  gap: 2px;
  padding: 2px;
  border-radius: $ops-radius-control;
  background: rgba(255, 255, 255, 0.15);
}

.hero__segment {
  padding: 6px 12px;
  border: none;
  border-radius: $ops-radius-control;
  background: transparent;
  color: $brand-white;
  font-family: inherit;
  font-size: $font-caption;
  font-weight: 500;
  text-transform: uppercase;
  cursor: pointer;
}

.hero__segment--active {
  background: rgba(255, 255, 255, 0.25);
}

.hero__chart {
  padding: 0 16px 16px;
}

.hero__canvas {
  width: 100%;
  height: 300px;
}
</style>

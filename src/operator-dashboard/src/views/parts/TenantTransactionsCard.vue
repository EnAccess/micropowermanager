<template>
  <ops-card :title="$tc('phrases.transactionsPerMonth')">
    <div class="tenant-chart">
      <v-chart
        :option="option"
        :autoresize="true"
        class="tenant-chart__canvas"
      />
    </div>
  </ops-card>
</template>

<script>
import { tenantLineOption } from "@/charts/transactionOptions.js"
import { formatCount, formatPeriodLabel } from "@/Helpers/format.js"
import OpsCard from "@/shared/OpsCard.vue"

export default {
  name: "TenantTransactionsCard",
  components: { OpsCard },
  props: {
    monthly: {
      type: Object,
      required: true,
    },
  },
  computed: {
    option() {
      return tenantLineOption({
        labels: this.monthly.periods.map(formatPeriodLabel),
        counts: this.monthly.transactions,
        formatValue: (value) =>
          this.$tc("phrases.transactionCount", 1, {
            value: formatCount(value),
          }),
        formatAxisValue: (value) =>
          value >= 1000 ? `${value / 1000}k` : value,
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.tenant-chart {
  padding: 0 24px 20px;
}

.tenant-chart__canvas {
  width: 100%;
  height: 250px;
}
</style>

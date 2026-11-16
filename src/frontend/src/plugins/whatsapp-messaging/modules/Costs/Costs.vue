<template>
  <div>
    <div class="md-layout md-gutter cost-filters">
      <md-datepicker
        v-for="field in ['from', 'to']"
        :key="field"
        v-model="range[field]"
        class="md-layout-item md-size-20 md-small-size-50"
        md-immediately
      >
        <label>{{ $tc(`words.${field}`) }}</label>
      </md-datepicker>
    </div>

    <div class="md-layout md-gutter cost-summary">
      <div
        v-for="tile in summaryTiles"
        :key="tile.label"
        class="md-layout-item md-size-33 md-small-size-100"
      >
        <box
          :box-color="tile.color"
          :box-icon="tile.icon"
          :header-text="tile.label"
          :sub-text="tile.value"
        />
      </div>
    </div>

    <widget :title="$tc('menu.subMenu.Costs')" color="primary">
      <p class="cost-hint">{{ $t("phrases.whatsappCostsHint") }}</p>
      <md-table>
        <md-table-row>
          <md-table-head>{{ $tc("words.category") }}</md-table-head>
          <md-table-head md-numeric>
            {{ $t("phrases.whatsappMessagesDelivered") }}
          </md-table-head>
          <md-table-head md-numeric>
            {{ $tc("words.cost") }} ({{ costs.currency }})
          </md-table-head>
        </md-table-row>
        <md-table-row v-for="row in categoryRows" :key="row.category">
          <md-table-cell>{{ row.label }}</md-table-cell>
          <md-table-cell md-numeric>{{ row.volume }}</md-table-cell>
          <md-table-cell md-numeric>{{ formatCost(row.cost) }}</md-table-cell>
        </md-table-row>
        <md-table-row v-if="!categoryRows.length">
          <md-table-cell colspan="3">
            {{ $tc("phrases.noData", 2) }}
          </md-table-cell>
        </md-table-row>
      </md-table>
      <v-chart
        v-if="categoryRows.length"
        :option="chartOption"
        autoresize
        class="cost-chart"
      />
    </widget>
  </div>
</template>

<script>
import moment from "moment"

import { WhatsappPricingService } from "../../services/WhatsappPricingService.js"

import { notify } from "@/mixins/notify.js"
import Box from "@/shared/Box.vue"
import Widget from "@/shared/Widget.vue"

// Meta prices messages in fractions of a cent, so two decimals would hide real costs.
const costFormat = new Intl.NumberFormat(undefined, {
  minimumFractionDigits: 2,
  maximumFractionDigits: 4,
})

export default {
  name: "Costs",
  components: { Box, Widget },
  mixins: [notify],
  data() {
    return {
      pricingService: new WhatsappPricingService(),
      range: { from: moment().subtract(30, "days").toDate(), to: new Date() },
      costs: {
        currency: "",
        totalCost: 0,
        paidVolume: 0,
        freeVolume: 0,
        categories: {},
        daily: {},
      },
    }
  },
  computed: {
    summaryTiles() {
      return [
        {
          label: this.$t("phrases.whatsappTotalCost"),
          value: `${this.formatCost(this.costs.totalCost)} ${this.costs.currency}`,
          color: "green",
          icon: "payments",
        },
        {
          label: this.$t("phrases.whatsappPaidMessages"),
          value: String(this.costs.paidVolume),
          color: "orange",
          icon: "paid",
        },
        {
          label: this.$t("phrases.whatsappFreeMessages"),
          value: String(this.costs.freeVolume),
          color: "blue",
          icon: "money_off",
        },
      ]
    },
    categoryRows() {
      return Object.entries(this.costs.categories)
        .map(([category, total]) => ({
          category,
          label: this.categoryLabel(category),
          ...total,
        }))
        .sort((first, second) => second.cost - first.cost)
    },
    chartOption() {
      const days = Object.keys(this.costs.daily).sort()
      return {
        title: { text: this.$t("phrases.whatsappDailyCost"), left: "center" },
        tooltip: {
          trigger: "axis",
          valueFormatter: (value) =>
            `${this.formatCost(value)} ${this.costs.currency}`,
        },
        legend: { bottom: 0 },
        grid: { bottom: 60 },
        xAxis: { type: "category", data: days },
        yAxis: { type: "value", name: this.costs.currency },
        series: this.categoryRows
          .filter((row) => row.cost > 0)
          .map(({ category, label }) => ({
            name: label,
            type: "bar",
            stack: "cost",
            data: days.map((day) => this.costs.daily[day][category] ?? 0),
          })),
      }
    },
  },
  watch: {
    range: { handler: "loadCosts", deep: true, immediate: true },
  },
  methods: {
    async loadCosts() {
      const { from, to } = this.range
      try {
        this.costs = await this.pricingService.getCosts(
          from && moment(from).format("YYYY-MM-DD"),
          to && moment(to).format("YYYY-MM-DD"),
        )
      } catch (error) {
        this.alertNotify(
          "error",
          error.response?.data?.message || error.message,
        )
        if (error.response?.status === 404) {
          this.$router.push("/whatsapp-messaging/overview")
        }
      }
    },
    formatCost(cost) {
      return costFormat.format(cost)
    },
    // Meta category codes such as MARKETING_LITE become "Marketing lite".
    categoryLabel(category) {
      const words = category.toLowerCase().replaceAll("_", " ")
      return words.charAt(0).toUpperCase() + words.slice(1)
    },
  },
}
</script>

<style lang="scss" scoped>
.cost-filters,
.cost-summary {
  padding-left: 24px;
  padding-right: 16px;
}

.cost-hint {
  padding: 0 16px;
  color: rgba(0, 0, 0, 0.6);
}

.cost-chart {
  height: 340px;
  margin-top: 16px;
}
</style>

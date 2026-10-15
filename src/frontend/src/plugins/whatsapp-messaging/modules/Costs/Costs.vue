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

    <widget :title="$tc('menu.subMenu.Costs')" color="primary">
      <md-table>
        <md-table-row>
          <md-table-head>{{ $tc("words.type") }}</md-table-head>
          <md-table-head md-numeric>{{ $tc("words.volume") }}</md-table-head>
          <md-table-head md-numeric>
            {{ $tc("words.cost") }} ({{ currency }})
          </md-table-head>
        </md-table-row>
        <md-table-row v-for="(total, type) in summaryRows" :key="type">
          <md-table-cell>{{ type }}</md-table-cell>
          <md-table-cell md-numeric>{{ total.volume }}</md-table-cell>
          <md-table-cell md-numeric>{{ total.cost.toFixed(2) }}</md-table-cell>
        </md-table-row>
      </md-table>
      <v-chart :option="chartOption" autoresize class="cost-chart" />
    </widget>
  </div>
</template>

<script>
import moment from "moment"

import { WhatsappPricingService } from "../../services/WhatsappPricingService.js"

import { notify } from "@/mixins/notify.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Costs",
  components: { Widget },
  mixins: [notify],
  data() {
    return {
      pricingService: new WhatsappPricingService(),
      range: { from: moment().subtract(30, "days").toDate(), to: new Date() },
      currency: "",
      totals: {},
      daily: {},
    }
  },
  computed: {
    summaryRows() {
      const sum = Object.values(this.totals).reduce(
        (sum, total) => ({
          volume: sum.volume + total.volume,
          cost: sum.cost + total.cost,
        }),
        { volume: 0, cost: 0 },
      )
      return { ...this.totals, [this.$tc("words.total")]: sum }
    },
    chartOption() {
      const days = Object.keys(this.daily).sort()
      return {
        tooltip: { trigger: "axis" },
        legend: {},
        xAxis: { type: "category", data: days },
        yAxis: { type: "value", name: this.currency },
        series: Object.keys(this.totals).map((type) => ({
          name: type,
          type: "bar",
          stack: "cost",
          data: days.map((day) => this.daily[day][type] ?? 0),
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
        const costs = await this.pricingService.getCosts(
          from && moment(from).format("YYYY-MM-DD"),
          to && moment(to).format("YYYY-MM-DD"),
        )
        this.currency = costs.currency
        this.totals = costs.totals
        this.daily = costs.daily
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
  },
}
</script>

<style lang="scss" scoped>
.cost-filters {
  padding-left: 24px;
  padding-right: 16px;
}

.cost-chart {
  height: 300px;
  margin-top: 16px;
}
</style>

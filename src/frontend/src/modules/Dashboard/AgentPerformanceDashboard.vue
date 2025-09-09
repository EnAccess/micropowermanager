<template>
  <div>
    <md-toolbar class="md-dense">
      <h3 class="md-title" style="flex: 1">
        {{ $tc("phrases.agentPerformanceDashboard") }}
      </h3>
      <md-menu
        md-direction="bottom-end"
        md-size="big"
        :md-offset-x="127"
        :md-offset-y="-36"
      >
        <md-button md-menu-trigger>
          <md-icon>keyboard_arrow_down</md-icon>
          {{ $tc("words.period") }}:
          {{ selectedPeriodLabel }}
        </md-button>
        <md-menu-content>
          <md-menu-item
            v-for="period in periodOptions"
            :key="period.value"
            @click="setPeriod(period.value)"
          >
            <span>{{ period.label }}</span>
          </md-menu-item>
        </md-menu-content>
      </md-menu>

      <md-button class="md-raised" @click="refreshData" :disabled="loading">
        <md-icon>update</md-icon>
        {{ $tc("phrases.refreshData") }}
        <md-progress-bar
          v-if="loading"
          md-mode="indeterminate"
        ></md-progress-bar>
      </md-button>
    </md-toolbar>

    <div>
      <div class="md-layout md-gutter" style="margin-top: 3rem">
        <!-- Key Performance Indicators -->
        <div class="md-layout-item md-size-100">
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'blue'"
                :center-text="true"
                :header-text="$tc('phrases.numberOfAgents')"
                :sub-text="metrics.number_of_agents?.toString() || '0'"
                :box-icon="'supervisor_account'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'orange'"
                :center-text="true"
                :header-text="$tc('phrases.avgCustomersPerAgent')"
                :sub-text="metrics.avg_customers_per_agent?.toString() || '0'"
                :box-icon="'people'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'green'"
                :center-text="true"
                :header-text="$tc('phrases.totalCommission')"
                :sub-text="formatCurrency(metrics.total_commission || 0)"
                :box-icon="'attach_money'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'red'"
                :center-text="true"
                :header-text="$tc('phrases.soldAppliances')"
                :sub-text="metrics.sold_appliances?.toString() || '0'"
                :box-icon="'devices'"
              />
            </div>
          </div>
        </div>

        <!-- Top Performing Agents Table -->
        <div
          class="md-layout-item md-size-30 md-medium-size-50 md-small-size-100"
        >
          <widget :title="$tc('phrases.topPerformingAgents')" id="top-agents">
            <md-table md-card style="margin-left: 0">
              <md-table-row>
                <md-table-head>{{ $tc("words.agent") }}</md-table-head>
                <md-table-head>{{ $tc("words.customers") }}</md-table-head>
                <md-table-head>{{ $tc("words.commission") }}</md-table-head>
                <md-table-head>{{ $tc("words.sales") }}</md-table-head>
              </md-table-row>
              <md-table-row
                v-for="agent in topAgents"
                :key="agent.agent"
                style="cursor: pointer"
                @click="viewAgentDetail(agent.agent)"
              >
                <md-table-cell>{{ agent.agent }}</md-table-cell>
                <md-table-cell>{{ agent.customers }}</md-table-cell>
                <md-table-cell>
                  {{ formatCurrency(agent.commission) }}
                </md-table-cell>
                <md-table-cell>{{ agent.sales }}</md-table-cell>
              </md-table-row>
            </md-table>
          </widget>
        </div>

        <!-- Performance Charts - Side by Side with Table -->
        <div
          class="md-layout-item md-size-70 md-medium-size-50 md-small-size-100"
        >
          <widget
            :title="$tc('phrases.performanceTrends')"
            id="performance-charts"
          >
            <div class="charts-container">
              <div class="chart-item">
                <chart-card
                  type="LineChart"
                  :header-text="$tc('phrases.commissionTrends')"
                  :chartData="commissionChartData"
                  :chartOptions="chartOptions"
                  :extendable="false"
                />
              </div>
              <div class="chart-item">
                <chart-card
                  type="LineChart"
                  :header-text="$tc('phrases.salesTrends')"
                  :chartData="salesChartData"
                  :chartOptions="chartOptions"
                  :extendable="false"
                />
              </div>
            </div>
          </widget>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Box from "@/shared/Box.vue"
import Widget from "@/shared/Widget.vue"
import ChartCard from "@/shared/ChartCard.vue"
import { AgentDashboardService } from "@/services/AgentDashboardService"
import { notify } from "@/mixins/notify"
import { currency } from "@/mixins/currency"

export default {
  name: "AgentPerformanceDashboard",
  components: { Box, Widget, ChartCard },
  mixins: [notify, currency],
  data() {
    return {
      loading: false,
      selectedPeriod: "monthly",
      agentDashboardService: new AgentDashboardService(),
      metrics: {},
      topAgents: [],
      periodData: {},
      periodOptions: [
        { value: "daily", label: this.$tc("words.daily") },
        { value: "weekly", label: this.$tc("words.weekly") },
        { value: "monthly", label: this.$tc("words.monthly") },
        { value: "yearly", label: this.$tc("words.yearly") },
      ],
    }
  },
  computed: {
    selectedPeriodLabel() {
      const option = this.periodOptions.find(
        (p) => p.value === this.selectedPeriod,
      )
      return option ? option.label : this.selectedPeriod
    },
    commissionChartData() {
      if (!this.periodData || Object.keys(this.periodData).length === 0) {
        return [["Period", "Commission"]]
      }

      const chartData = [["Period", "Commission"]]
      Object.entries(this.periodData).forEach(([period, periodData]) => {
        chartData.push([period, periodData.agent_commissions || 0])
      })
      return chartData
    },
    salesChartData() {
      if (!this.periodData || Object.keys(this.periodData).length === 0) {
        return [["Period", "Sales"]]
      }

      const chartData = [["Period", "Sales"]]
      Object.entries(this.periodData).forEach(([period, periodData]) => {
        chartData.push([period, periodData.appliance_sales || 0])
      })
      return chartData
    },
    chartOptions() {
      return {
        chart: {
          title: "",
          subtitle: "",
        },
        hAxis: {
          title: this.$tc("words.period"),
        },
        vAxis: {
          title: this.$tc("words.value"),
        },
        colors: ["#26c6da", "#ffa726"],
        legend: { position: "top" },
        height: 400,
        width: "100%",
      }
    },
  },
  mounted() {
    this.loadAgentPerformanceData()
  },
  methods: {
    async loadAgentPerformanceData() {
      this.loading = true
      try {
        const data =
          await this.agentDashboardService.getAgentPerformanceMetrics(
            this.selectedPeriod,
          )
        this.metrics = data.data.metrics || {}
        this.topAgents = data.data.top_agents || []
        this.periodData = data.data.period || {}
      } catch (error) {
        this.alertNotify("error", "Failed to load agent performance data")
      } finally {
        this.loading = false
      }
    },
    setPeriod(period) {
      this.selectedPeriod = period
      this.loadAgentPerformanceData()
    },
    async refreshData() {
      await this.loadAgentPerformanceData()
      this.alertNotify(
        "success",
        "Agent performance data refreshed successfully",
      )
    },
    viewAgentDetail(agentName) {
      // Navigate to agent detail page
      this.$router.push({ path: `/agents`, query: { search: agentName } })
    },
    formatCurrency(amount) {
      const currency =
        this.$store.getters["settings/getMainSettings"]?.currency || "TSZ"
      return this.readable(amount) + currency
    },
  },
}
</script>

<style lang="scss" scoped>
.period-selector {
  margin-right: 2rem;
  min-width: 120px;
  max-width: 150px;
  flex-shrink: 0;
}

.period-selector .md-field {
  margin: 0;
  padding: 0;
  min-height: auto;
  border: none;
}

.period-selector .md-field .md-input {
  margin: 0;
  padding: 4px 0;
  font-size: 14px;
  min-height: 32px;
  line-height: 1.2;
}

.period-selector .md-field .md-input:before,
.period-selector .md-field .md-input:after {
  display: none;
}

.period-selector .md-field .md-select {
  padding: 0;
  margin: 0;
}

.md-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  overflow: visible;
  z-index: 1;
  min-height: 48px;
  padding: 8px 16px;
}

.md-toolbar .md-title {
  flex: 1;
  margin: 0;
}

.md-toolbar .period-selector {
  margin: 0 2rem 0 0;
  position: relative;
  z-index: 2;
}

.md-toolbar .md-button {
  margin-left: auto;
  flex-shrink: 0;
}

.md-menu {
  z-index: 1000;
}

.period-selector .md-button {
  border: none !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  background: transparent !important;
}

.period-selector .md-button:hover {
  background: rgba(0, 0, 0, 0.04) !important;
}

.charts-container {
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.chart-item {
  width: 100%;
  min-height: 350px;
}

.chart-item .md-layout-item {
  width: 100% !important;
  max-width: 100% !important;
  flex: 0 0 100% !important;
}

.chart-item .chart-card {
  width: 100%;
  margin-bottom: 1vh;
  min-height: 100%;
}

.chart-item .md-layout-item.md-size-33 {
  width: 100% !important;
  flex: 0 0 100% !important;
}

.chart-item .md-layout-item.md-medium-size-100 {
  width: 100% !important;
  flex: 0 0 100% !important;
}

.chart-item .chart-card .md-card-content {
  width: 100%;
}

.chart-item .chart-card .md-card-content > div {
  width: 100%;
}

@media (max-width: 768px) {
  .charts-container {
    padding: 5px;
    gap: 15px;
  }

  .chart-item {
    min-height: 300px;
  }
}
</style>

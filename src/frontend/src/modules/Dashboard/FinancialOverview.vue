<template>
  <widget
    :title="$tc('phrases.financeOverview', 0, { period: periodText })"
    :id="'clusters-finance-overview'"
    button
    button-text="Set Period"
    button-color="red"
    @widgetAction="showPeriod"
    button-icon="calendar_today"
  >
    <div v-if="setPeriod" class="period-selector">
      <p>{{ $tc("phrases.selectPeriod") }}</p>
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <md-datepicker
            :class="{
              'md-invalid': errors.has($tc('phrases.fromDate')),
            }"
            :name="$tc('phrases.fromDate')"
            md-immediately
            v-model="period.from"
            v-validate="'required'"
            :md-close-on-blur="false"
          >
            <label>{{ $tc("phrases.fromDate") }}</label>
            <span class="md-error">
              {{ errors.first($tc("phrases.fromDate")) }}
            </span>
          </md-datepicker>
        </div>
        <div class="md-layout-item md-size-100">
          <md-datepicker
            :class="{
              'md-invalid': errors.has($tc('phrases.toDate')),
            }"
            :name="$tc('phrases.toDate')"
            md-immediately
            v-model="period.to"
            v-validate="'required'"
            :md-close-on-blur="false"
          >
            <label>{{ $tc("phrases.toDate") }}</label>
            <span class="md-error">
              {{ errors.first($tc("phrases.toDate")) }}
            </span>
          </md-datepicker>
        </div>
      </div>
      <div style="margin-top: 5px">
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <button
          style="width: 100%"
          v-if="!loading"
          class="btn btn-primary"
          @click="getClusterFinancialData"
        >
          {{ $tc("words.send") }}
        </button>
      </div>
    </div>
    <div class="md-layout md-gutter" style="padding: 10px">
      <chart-card
        type="LineChart"
        :header-text="$tc('phrases.revenueLine')"
        :chartData="lineChartData"
        :chartOptions="chartOptions"
        :extendable="true"
      />
      <chart-card
        type="ColumnChart"
        :header-text="$tc('phrases.revenueColumns')"
        :chartData="columnChartData"
        :chartOptions="chartOptions"
        :extendable="true"
      />
      <chart-card
        type="PieChart"
        :header-text="$tc('phrases.revenuePercentiles')"
        :chartData="pieChartData"
        :chartOptions="chartOptions"
        :extendable="true"
      />
    </div>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import moment from "moment"
import { ClusterService } from "@/services/ClusterService"
import ChartCard from "@/shared/ChartCard.vue"
import { notify } from "@/mixins/notify"

export default {
  name: "FinancialOverview",
  components: { ChartCard, Widget },
  mixins: [notify],
  props: {
    clusterId: {
      type: [Number, null],
      default: null,
    },
    revenue: {
      required: true,
    },
    periodChanged: {
      type: Function,
      required: true,
    },
  },
  data() {
    return {
      clusterService: new ClusterService(),
      period: {
        from: null,
        to: null,
      },
      loading: false,
      setPeriod: false,
      disabled: {
        customPredictor: function (date) {
          let today = new Date()
          let minDate = new Date("2018-01-01")
          if (date > today || date < minDate) {
            return true
          }
        },
      },
      chartOptions: {
        chart: {
          title: "",
          subtitle: "",
        },
      },
    }
  },
  mounted() {
    let currentDate = new Date()
    let startDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth() - 2,
      1,
    )
    let endDate = currentDate
    this.setDate(startDate, "from")
    this.setDate(endDate, "to")
    this.getClusterFinancialData()
  },
  watch: {
    "clusterService.financialData": {
      handler(newVal) {
        if (newVal) {
          this.lineChartData = this.clusterService.lineChartData(true)
          this.columnChartData = this.clusterService.columnChartData(
            false,
            "cluster",
          )
          this.pieChartData = this.clusterService.columnChartData(
            false,
            "cluster",
          )
        }
      },
      deep: true,
      immediate: true,
    },
  },
  methods: {
    showPeriod() {
      this.setPeriod = !this.setPeriod
    },
    async getClusterFinancialData() {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.loading = true

      try {
        const financialData = await this.clusterService.getAllRevenues(
          "monthly",
          this.period.from,
          this.period.to,
        )
        this.clusterService.financialData = financialData

        this.periodChanged(this.period.from, this.period.to)

        this.setPeriod = false
      } catch (error) {
        console.error("Error fetching financial data:", error)
      } finally {
        this.loading = false
      }
    },
    setDate(dateData, target) {
      let date = moment(dateData)
      if (target === "from") {
        this.period.from = date.format("YYYY-MM-DD")
      } else {
        this.period.to = date.format("YYYY-MM-DD")
      }
    },
  },
  computed: {
    periodText() {
      return this.period.from + " - " + this.period.to
    },
    lineChartData() {
      return this.clusterService.lineChartData(true)
    },
    columnChartData() {
      return this.clusterService.columnChartData(false, "cluster")
    },
    pieChartData() {
      return this.clusterService.columnChartData(false, "cluster")
    },
  },
}
</script>

<style>
.datepicker-right .vdp-datepicker__calendar {
  right: 0;
}

.period-selector {
  position: absolute;
  top: 0;
  right: 0;
  z-index: 9999;
  padding: 15px;
  background-color: white;
  border: 1px solid #ccc;
  margin-right: 1rem;
  margin-top: 2rem;
}
</style>

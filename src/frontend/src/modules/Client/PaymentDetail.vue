<template>
  <div>
    <widget
      :title="$tc('phrases.paymentOverview', 0, { period: periodName })"
      :subscriber="subscriber"
    >
      <div slot="tabbar">
        <md-field>
          <md-select
            class="period-style md-has-value"
            name="period"
            id="period"
            v-model="period"
            @md-selected="getFlow"
          >
            <md-option value="D">
              {{ $tc("words.day", 2) }}
            </md-option>
            <md-option value="W">
              {{ $tc("words.week", 2) }}
            </md-option>
            <md-option value="M">
              {{ $tc("words.month", 2) }}
            </md-option>
            <md-option value="Y">
              {{ $tc("words.annually") }}
            </md-option>
          </md-select>
        </md-field>
      </div>
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-90">
          <div style="height: 400px; width: 100%; position: relative">
            <v-chart
              v-if="
                echartsOption && echartsOption.series && echartsOption.series[0]
              "
              :option="echartsOption"
              :autoresize="true"
              style="height: 400px; width: 100%; min-height: 400px"
            />
            <div
              v-else
              style="
                padding: 2rem;
                text-align: center;
                color: #999;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
              "
            >
              {{ $tc("phrases.noData") || "No Data Available" }}
            </div>
          </div>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { PaymentService } from "@/services/PaymentService"
import { notify } from "@/mixins/notify"

export default {
  name: "PaymentDetail",
  mixins: [notify],
  data() {
    return {
      paymentService: new PaymentService(),
      subscriber: "payment-overview",
      contentWidth: 0,
      personId: null,
      period: "M",
      periodName: "Monthly",
      barData: [],
    }
  },
  computed: {
    echartsOption() {
      if (
        !this.paymentService.paymentDetailData ||
        !Array.isArray(this.paymentService.paymentDetailData) ||
        this.paymentService.paymentDetailData.length < 2
      ) {
        return null
      }

      const data = this.paymentService.paymentDetailData
      const headers = data[0] || []
      const seriesNames = headers.slice(1)
      const categories = data.slice(1).map((row) => row[0] || "")

      const colors = ["#0b920b", "#8b2621", "#0c7cd5", "#aad4df"]

      const series = seriesNames.map((name, index) => {
        const seriesIndex = index + 1
        return {
          name: String(name || `Series ${index + 1}`),
          type: "bar",
          data: data.slice(1).map((row) => parseFloat(row[seriesIndex]) || 0),
          itemStyle: {
            color: colors[index % colors.length],
          },
        }
      })

      return {
        tooltip: {
          trigger: "axis",
          axisPointer: {
            type: "shadow",
          },
        },
        legend: {
          data: seriesNames.map((name) => String(name)),
          top: 10,
        },
        grid: {
          left: "3%",
          right: "4%",
          bottom: "3%",
          containLabel: true,
        },
        xAxis: {
          type: "category",
          data: categories,
        },
        yAxis: {
          type: "value",
        },
        series: series,
      }
    },
  },
  created() {
    this.personId = this.$store.getters.person.id
  },
  mounted() {
    this.getFlow()
  },
  components: {
    Widget,
  },
  methods: {
    async getFlow(period = "M") {
      if (!this.$can("payments")) {
        return
      }
      switch (period) {
        case "Y":
          this.periodName = this.$tc("words.annually")
          break
        case "M":
          this.periodName = this.$tc("words.month", 2)
          break
        case "W":
          this.periodName = this.$tc("words.week", 2)
          break
        case "D":
          this.periodName = this.$tc("words.day", 2)
          break
      }
      try {
        await this.paymentService.getPaymentDetail(this.personId, period)
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.paymentService.paymentDetailData.length,
        )
      } catch (e) {
        if (e.response && e.response.status === 403) {
          console.warn("Payment detail: Insufficient permissions")
          return
        }
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
<style scoped>
.payment-period-select {
  float: right;
  padding-right: 2.5rem !important;
  padding-left: 2.5rem !important;
}

.period-style {
  color: white !important;
  -webkit-text-fill-color: white !important;
}

#period input[type="text"] {
  color: white !important;
  -webkit-text-fill-color: white !important;
}
</style>

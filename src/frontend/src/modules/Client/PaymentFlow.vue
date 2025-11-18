<template>
  <widget
    :title="$tc('phrases.paymentFlow')"
    icon="money"
    :subscriber="subscriber"
  >
    <md-card>
      <md-card-header>
        <div class="md-title">
          <span class="txt-color-blue" id="flow_total">
            {{
              $tc("phrases.paymentFlow", 2, {
                currency: paymentSum[0],
                count: paymentSum[1],
              })
            }}
          </span>
        </div>
      </md-card-header>

      <md-card-content>
        <div class="md-layout">
          <div class="md-layout-item md-size-100">
            <div style="height: 400px; width: 100%; position: relative">
              <v-chart
                v-if="
                  echartsOption &&
                  echartsOption.series &&
                  echartsOption.series[0]
                "
                :options="echartsOption"
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
          <div class="md-layout-item md-size-100">
            {{ $tc("phrases.averagePeriod") }}
            <span class="txt-color-yellow">
              {{ paymentPeriod }}
            </span>
          </div>
          <div class="md-layout-item md-size-100">
            {{ $tc("phrases.lastPayment") }}
            <span
              :class="
                parseInt(lastPayment) < parseInt(paymentPeriod)
                  ? 'txt-color-green'
                  : 'txt-color-red'
              "
            >
              {{ lastPayment }}
            </span>
          </div>
          <div class="md-layout-item md-size-100">
            {{ $tc("phrases.accessRateDebt") }}
            <span
              :class="
                parseInt(accessDebt) == 0 ? 'txt-color-green' : 'txt-color-red'
              "
            >
              {{ accessDebt }}
            </span>
          </div>
          <div class="md-layout-item md-size-100">
            {{ $tc("phrases.deferredDebt") }}
            <span
              :class="
                parseInt(deferredDebt) == 0
                  ? 'txt-color-green'
                  : 'txt-color-red'
              "
            >
              {{ deferredDebt }}
            </span>
          </div>
        </div>
      </md-card-content>
    </md-card>
  </widget>
</template>

<script>
import { currency } from "@/mixins/currency"
import Widget from "@/shared/Widget.vue"
import { PaymentService } from "@/services/PaymentService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "PaymentFlow",
  components: {
    Widget,
  },
  mixins: [currency, notify],
  data() {
    return {
      paymentService: new PaymentService(),
      subscriber: "payment-flow",
      monthNames: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "June",
        "July",
        "Aug",
        "Sept",
        "Oct",
        "Nov",
        "Dec",
      ],
      lastPayment: null,
      paymentPeriod: 0,
      loaded: false,
      accessDebt: 0,
      deferredDebt: 0,
    }
  },
  computed: {
    paymentSum() {
      let cur = this.$store.getters["settings/getMainSettings"].currency

      let currentMonth = new Date().getMonth()
      let pass = true
      let total = 0
      let paidMonths = 0
      for (let i = 0; i < this.paymentService.flow.length; i++) {
        if (currentMonth < i) break
        let flowVal = this.paymentService.flow[i]
        if (flowVal > 0) {
          pass = false
        }
        if (pass) {
          continue
        }
        paidMonths++
        total += flowVal
      }

      let result = total === 0 ? 0 : Math.round(total / paidMonths, 2)
      let paymentFlow = [this.readable(result) + cur, paidMonths.toString()]

      return paymentFlow
    },
    echartsOption() {
      if (
        !this.paymentService.chartData ||
        !Array.isArray(this.paymentService.chartData) ||
        this.paymentService.chartData.length < 2
      ) {
        return null
      }

      const data = this.paymentService.chartData
      const headers = data[0] || []
      const seriesNames = headers.slice(1)
      const categories = data.slice(1).map((row) => row[0] || "")

      const colors = ["#1b9e77", "#d95f02", "#7570b3"]

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
    this.getFlow(this.$store.getters.person.id)
    this.getPeriod(this.$store.getters.person.id)
    this.getDebt(this.$store.getters.person.id)
  },
  methods: {
    async getFlow(personId) {
      try {
        await this.paymentService.getPaymentFlow(personId)
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.paymentService.chartData.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getPeriod(personId) {
      try {
        let data = await this.paymentService.getPeriod(personId)
        this.paymentPeriod = data.difference
        this.lastPayment = data.lastTransaction
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getDebt(personId) {
      try {
        let data = await this.paymentService.getDebt(personId)
        this.accessDebt = data.access_rate
        this.deferredDebt = data.deferred
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
<style lang="css" scoped>
.txt-color-green {
  color: green;
}

.txt-color-red {
  color: red;
}

.txt-color-yellow {
  color: #cccc05;
}
</style>

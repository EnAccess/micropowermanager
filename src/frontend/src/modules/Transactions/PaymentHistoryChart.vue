<template>
  <div style="height: 300px; width: 100%; position: relative">
    <v-chart
      v-if="
        echartsOption &&
        echartsOption.series &&
        echartsOption.series[0] &&
        echartsOption.series[0].data &&
        echartsOption.series[0].data.length > 0
      "
      :options="echartsOption"
      :autoresize="true"
      style="height: 300px; width: 100%; min-height: 300px"
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
</template>

<script>
export default {
  name: "PaymentHistoryChart",
  props: ["paymentdata"],
  data() {
    return {
      donutData: [["Paid For", "Amount"]],
    }
  },
  computed: {
    echartsOption() {
      if (
        !this.donutData ||
        !Array.isArray(this.donutData) ||
        this.donutData.length < 2
      ) {
        return null
      }

      const headers = this.donutData[0]
      const labelHeader = headers[0] || "Paid For"

      const data = this.donutData.slice(1).map((row) => ({
        name: String(row[0] || ""),
        value: parseFloat(row[1]) || 0,
      }))

      const validData = data.filter((item) => item.value > 0)

      if (validData.length === 0) {
        return null
      }

      return {
        title: {
          text: this.$tc("phrases.paymentDistribution"),
          left: "center",
          top: 10,
        },
        tooltip: {
          trigger: "item",
        },
        legend: {
          orient: "vertical",
          left: "left",
          top: "middle",
        },
        series: [
          {
            name: labelHeader,
            type: "pie",
            radius: ["40%", "70%"],
            center: ["60%", "50%"],
            avoidLabelOverlap: false,
            itemStyle: {
              borderColor: "#fff",
              borderWidth: 2,
            },
            label: {
              show: true,
              color: "white",
            },
            emphasis: {
              label: {
                show: true,
                fontSize: 20,
                fontWeight: "bold",
              },
            },
            data: validData,
          },
        ],
      }
    },
  },
  methods: {
    prepareChartData() {
      this.donutData = [["Paid For", "Amount"]]
      for (let i in this.paymentdata) {
        this.donutData.push([
          this.paymentdata[i].payment_type,
          this.paymentdata[i].amount,
        ])
      }
      return this.donutData
    },
  },
  mounted() {
    this.prepareChartData()
  },
}
</script>

<style scoped></style>

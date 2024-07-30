<template>
  <div>
    <GChart
      v-if="donutData.length > 0"
      type="PieChart"
      :data="donutData"
      :options="donutChartOptions"
    />
  </div>
</template>

<script>
export default {
  name: "PaymentHistoryChart",
  props: ["paymentdata"],
  data() {
    return {
      donutData: [["Paid For", "Amount"]],
      donutChartOptions: {
        title: this.$tc("phrases.paymentDistribution"),
        pieHole: 0.4,
        legend: "left",
        height: 300,
        pieSliceTextStyle: {
          color: "white",
        },
      },
    }
  },
  methods: {
    prepareChartData() {
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

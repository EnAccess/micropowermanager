<template>
  <div>
    <widget
      :id="'revenue-targets'"
      :headless="true"
      :title="$tc('phrases.revenueTargetsPerCustomerType')"
      color="green"
    >
      <div v-if="loading">
        <loader size="sm" />
      </div>
      <div v-else style="height: 500px; width: 100%; position: relative">
        <v-chart
          v-if="
            echartsOption &&
            echartsOption.series &&
            echartsOption.series[0] &&
            echartsOption.series[0].data &&
            echartsOption.series[0].data.length > 0
          "
          :option="echartsOption"
          :autoresize="true"
          style="height: 500px; width: 100%; min-height: 500px"
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
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import Loader from "@/shared/Loader.vue"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "RevenueTargetPerCustomerType",
  components: { Loader, Widget },
  props: {
    targetRevenueChartData: {
      required: true,
    },
  },
  mounted() {
    EventBus.$on("miniGridCachedDataLoading", (loading) => {
      this.loading = loading
    })
  },
  data() {
    return {
      loading: false,
    }
  },
  computed: {
    echartsOption() {
      if (
        !this.targetRevenueChartData ||
        !Array.isArray(this.targetRevenueChartData) ||
        this.targetRevenueChartData.length < 2
      ) {
        return null
      }

      const data = this.targetRevenueChartData
      const categoryIndex = 0
      const valueIndex = 1

      const categories = data.slice(1).map((row) => row[categoryIndex] || "")
      const values = data
        .slice(1)
        .map((row) => parseFloat(row[valueIndex]) || 0)

      return {
        tooltip: {
          trigger: "axis",
        },
        grid: {
          left: "3%",
          right: "4%",
          bottom: "15%",
          containLabel: true,
        },
        xAxis: {
          type: "category",
          data: categories,
          axisLabel: {
            rotate: 45,
            interval: 0,
            fontSize: 8,
          },
        },
        yAxis: {
          type: "value",
          min: 0,
          max: 1,
          axisLabel: {
            formatter: (value) => {
              return (value * 100).toFixed(0) + "%"
            },
          },
          name:
            (this.$tc("phrases.percentageOfTargetedRevenue") ||
              "Percentage of Targeted Revenue") + " %",
          nameLocation: "middle",
          nameGap: 50,
        },
        series: [
          {
            name:
              this.$tc("phrases.percentageOfTargetedRevenue") || "Percentage",
            type: "bar",
            data: values,
            itemStyle: {
              color: (params) => {
                const colors = [
                  "#5470c6",
                  "#91cc75",
                  "#fac858",
                  "#ee6666",
                  "#73c0de",
                  "#3ba272",
                  "#fc8452",
                  "#9a60b4",
                  "#ea7ccc",
                  "#5470c6",
                  "#91cc75",
                  "#fac858",
                  "#ee6666",
                  "#73c0de",
                  "#3ba272",
                ]
                return colors[params.dataIndex % colors.length]
              },
            },
          },
        ],
      }
    },
  },
  methods: {},
}
</script>

<style scoped></style>

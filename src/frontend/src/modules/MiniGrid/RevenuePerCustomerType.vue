<template>
  <div>
    <widget
      :id="'revenue-pie'"
      :headless="true"
      :title="$tc('phrases.revenuePerCustomerType')"
      color="red"
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
          :options="echartsOption"
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
  name: "RevenuePerCustomerType",
  components: { Loader, Widget },
  props: {
    donutChartOptions: {
      required: true,
    },
    donutData: {
      required: true,
    },
  },
  data() {
    return {
      loading: false,
    }
  },
  computed: {
    echartsOption() {
      if (
        !this.donutData ||
        !Array.isArray(this.donutData) ||
        this.donutData.length < 2
      ) {
        return {
          title: {
            text: this.$tc("phrases.noData") || "No Data",
            left: "center",
            top: "middle",
            textStyle: {
              color: "#999",
              fontSize: 16,
            },
          },
        }
      }

      const headers = this.donutData[0]
      const labelHeader = headers[0] || "Connection"
      const valueHeader = headers[1] || "Revenue"

      const data = this.donutData.slice(1).map((row) => ({
        name: String(row[0] || ""),
        value: parseFloat(row[1]) || 0,
      }))

      const validData = data.filter((item) => item.value > 0)

      if (validData.length === 0) {
        return {
          title: {
            text: this.$tc("phrases.noData") || "No Data",
            left: "center",
            top: "middle",
            textStyle: {
              color: "#999",
              fontSize: 16,
            },
          },
        }
      }

      const option = {
        tooltip: {
          trigger: "item",
          formatter: (params) => {
            if (!params || !params.data) return ""
            const percent = (params.percent || 0).toFixed(1)
            return `${params.name}<br/>${valueHeader}: ${this.formatValue(
              params.value || params.data.value,
            )}<br/>${percent}%`
          },
        },
        legend: {
          orient: "horizontal",
          bottom: 10,
          type: "scroll",
          data: validData.map((item) => item.name),
          pageButtonItemGap: 5,
          pageButtonGap: 10,
          pageButtonPosition: "end",
          pageFormatter: "{current}/{total}",
          pageIconColor: "#2f4554",
          pageIconInactiveColor: "#aaa",
          pageIconSize: 15,
          pageTextStyle: {
            color: "#333",
            fontSize: 12,
          },
          textStyle: {
            fontSize: 12,
          },
          itemGap: 15,
        },
        series: [
          {
            name: labelHeader,
            type: "pie",
            radius: ["40%", "70%"],
            center: ["50%", "45%"],
            avoidLabelOverlap: false,
            itemStyle: {
              borderColor: "#fff",
              borderWidth: 2,
            },
            label: {
              show: false,
            },
            emphasis: {
              label: {
                show: true,
                fontSize: 20,
                fontWeight: "bold",
                formatter: (params) => {
                  return `${params.name}\n${this.formatValue(params.value)}`
                },
              },
              itemStyle: {
                shadowBlur: 10,
                shadowOffsetX: 0,
                shadowColor: "rgba(0, 0, 0, 0.5)",
              },
            },
            labelLine: {
              show: false,
            },
            data: validData,
          },
        ],
      }

      return option
    },
  },
  mounted() {
    EventBus.$on("miniGridCachedDataLoading", (loading) => {
      this.loading = loading
    })
  },
  methods: {
    formatValue(value) {
      if (typeof value !== "number") return "0"
      return value.toLocaleString("en-US", {
        maximumFractionDigits: 0,
      })
    },
  },
}
</script>

<style scoped></style>

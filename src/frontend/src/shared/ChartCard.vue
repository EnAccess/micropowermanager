<template>
  <div
    class="md-layout-item md-medium-size-100"
    :class="wide ? 'md-size-100' : 'md-size-33'"
  >
    <md-card class="chart-card">
      <md-card-header>
        <md-card-header-text>
          <div class="chart-header-text">{{ headerText }}</div>
        </md-card-header-text>
        <md-menu
          v-if="extendable"
          class="md-medium-hide"
          md-size="big"
          md-direction="bottom-end"
        >
          <md-button class="md-icon-button" md-menu-trigger @click="maximize">
            <md-icon>fullscreen</md-icon>
          </md-button>
        </md-menu>
      </md-card-header>
      <md-card-content>
        <div v-if="loading">
          <loader size="sm" />
        </div>
        <div v-else style="height: 250px; width: 100%; position: relative">
          <v-chart
            v-if="
              echartsOption && echartsOption.series && echartsOption.series[0]
            "
            :options="echartsOption"
            :autoresize="true"
            style="height: 250px; width: 100%; min-height: 250px"
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
            No Data Available
          </div>
        </div>
      </md-card-content>
    </md-card>
  </div>
</template>

<script>
import Loader from "@/shared/Loader.vue"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "ChartCard",
  components: { Loader },
  props: {
    type: {
      type: String,
      required: true,
    },
    headerText: {
      type: String,
      required: true,
    },
    chartData: {
      // eslint-disable-next-line vue/require-prop-type-constructor
      type: Array | undefined,
      required: true,
    },
    chartOptions: {
      type: Object,
      required: true,
    },
    extendable: {
      type: Boolean,
      default: false,
    },
  },
  data: () => ({
    loading: false,
    fullScreen: false,
  }),
  mounted() {
    EventBus.$on("clustersCachedDataLoading", (loading) => {
      this.loading = loading
    })
  },
  methods: {
    maximize() {
      this.fullScreen = !this.fullScreen
      window.dispatchEvent(new Event("resize"))
    },
  },
  computed: {
    wide() {
      return this.fullScreen
    },
    echartsOption() {
      if (
        !this.chartData ||
        !Array.isArray(this.chartData) ||
        this.chartData.length < 2
      ) {
        return null
      }

      const headers = this.chartData[0] || []
      const isLineChart = this.type === "LineChart"
      const isPieChart = this.type === "PieChart"
      const seriesNames = headers.slice(1)

      if (isPieChart) {
        const data = this.chartData.slice(1).map((row) => ({
          name: String(row[0] || ""),
          value: parseFloat(row[1]) || 0,
        }))

        const validData = data.filter((item) => item.value > 0)

        if (validData.length === 0) {
          return null
        }

        return {
          tooltip: {
            trigger: "item",
          },
          legend: {
            orient: "vertical",
            left: "left",
          },
          series: [
            {
              name: headers[0] || "Category",
              type: "pie",
              radius: "50%",
              data: validData,
              emphasis: {
                itemStyle: {
                  shadowBlur: 10,
                  shadowOffsetX: 0,
                  shadowColor: "rgba(0, 0, 0, 0.5)",
                },
              },
            },
          ],
        }
      }

      const categories = this.chartData.slice(1).map((row) => row[0] || "")

      const series = seriesNames.map((name, index) => {
        const seriesIndex = index + 1
        return {
          name: String(name || `Series ${index + 1}`),
          type: isLineChart ? "line" : "bar",
          data: this.chartData
            .slice(1)
            .map((row) => parseFloat(row[seriesIndex]) || 0),
          smooth: isLineChart,
        }
      })

      return {
        tooltip: {
          trigger: "axis",
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
          boundaryGap: isLineChart ? false : true,
        },
        yAxis: {
          type: "value",
        },
        series: series,
      }
    },
  },
}
</script>

<style scoped>
.chart-card {
  margin-bottom: 1vh;
  min-height: 100%;
}

.chart-header-text {
  font-size: larger;
  font-weight: 300;
}
</style>

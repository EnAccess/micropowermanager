<template>
  <md-card>
    <md-card-header>
      <div class="chart-box-header">
        <div style="height: 300px; width: 100%; position: relative">
          <v-chart
            v-if="
              echartsOption && echartsOption.series && echartsOption.series[0]
            "
            :option="echartsOption"
            :autoresize="true"
            style="height: 300px; width: 100%; min-height: 300px"
          />
        </div>
      </div>
    </md-card-header>
    <md-card-content>
      <p class="chart-header-text">{{ title }}</p>
      <slot />
    </md-card-content>
  </md-card>
</template>

<script>
export default {
  name: "ChartBox",
  props: {
    title: {
      required: true,
      type: String,
    },
    data: {
      type: Array,
      default: () => [],
    },
    chartType: {
      type: String,
      default: "LineChart",
    },
    chartOptions: {
      type: Object,
      default: () => ({}),
    },
    gradientStart: {
      type: String,
      default: "#ffffff",
    },
    gradientEnd: {
      type: String,
      default: "#ffffff",
    },
  },
  computed: {
    echartsOption() {
      if (!this.data || !Array.isArray(this.data) || this.data.length < 2) {
        return null
      }

      const headers = this.data[0] || []
      const isLineChart = this.chartType === "LineChart"
      const seriesNames = headers.slice(1)

      const categories = this.data.slice(1).map((row) => row[0] || "")

      const series = seriesNames.map((name, index) => {
        const seriesIndex = index + 1
        return {
          name: String(name || `Series ${index + 1}`),
          type: isLineChart ? "line" : "bar",
          data: this.data
            .slice(1)
            .map((row) => parseFloat(row[seriesIndex]) || 0),
          smooth: isLineChart,
        }
      })

      return {
        backgroundColor: {
          type: "linear",
          x: 0,
          y: 0,
          x2: 1,
          y2: 1,
          colorStops: [
            { offset: 0, color: this.gradientStart },
            { offset: 1, color: this.gradientEnd },
          ],
        },
        tooltip: {
          trigger: "axis",
        },
        legend: {
          show: false,
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
          axisLabel: {
            color: "#f8dedd",
          },
        },
        yAxis: {
          type: "value",
          axisLabel: {
            color: "#f8dedd",
          },
        },
        series: series,
      }
    },
  },
}
</script>

<style scoped>
.chart-box-header {
  margin: -3rem 15px 0 15px;
  overflow: hidden;
  border-radius: 6px;
}

.chart-header-text {
  font-size: 1.1rem;
  color: #999999;
  margin: 0;
}
</style>

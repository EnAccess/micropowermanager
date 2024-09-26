<template>
  <md-card>
    <md-card-header>
      <div class="chart-box-header">
        <GChart
          :type="chartType"
          :data="data"
          :options="setChartBackGroundColor()"
          :resizeDebounce="500"
        />
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
      default: () => ({
        hAxis: {
          textStyle: {
            color: "#f8dedd",
          },
        },
        vAxis: {
          textStyle: {
            color: "#f8dedd",
          },
        },
        legend: {
          position: "none",
          textStyle: {
            color: "#f8dedd",
          },
        },
      }),
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
  methods: {
    setChartBackGroundColor() {
      this.chartOptions["backgroundColor"] = {
        gradient: {
          // Start color for gradient.
          color1: this.gradientStart, //'#fbf6a7',
          // Finish color for gradient.
          color2: this.gradientEnd, //'#33b679',
          // Where on the boundary to start and
          // end the color1/color2 gradient,
          // relative to the upper left corner
          // of the boundary.
          x1: "0%",
          y1: "0%",
          x2: "100%",
          y2: "100%",
          // If true, the boundary for x1,
          // y1, x2, and y2 is the box. If
          // false, it's the entire chart.
          useObjectBoundingBoxUnits: true,
        },
      }
      return this.chartOptions
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

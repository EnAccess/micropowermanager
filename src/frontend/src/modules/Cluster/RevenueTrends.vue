<template>
  <widget :id="'revenue-trends'" :title="'Revenue Trends'">
    <div v-if="loading">
      <loader />
    </div>
    <div class="md-layout md-gutter" v-else>
      <div class="md-layout-item md-size-100">
        <div style="height: 550px; width: 100%; position: relative">
          <v-chart
            v-if="
              columnChartOption &&
              columnChartOption.series &&
              columnChartOption.series[0] &&
              columnChartOption.series[0].data &&
              columnChartOption.series[0].data.length > 0
            "
            :options="columnChartOption"
            :autoresize="true"
            style="height: 550px; width: 100%; min-height: 550px"
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
      </div>
      <div class="md-layout-item md-size-100">
        <div style="height: 550px; width: 100%; position: relative">
          <v-chart
            v-if="
              lineChartOption &&
              lineChartOption.series &&
              lineChartOption.series[0] &&
              lineChartOption.series[0].data &&
              lineChartOption.series[0].data.length > 0
            "
            :options="lineChartOption"
            :autoresize="true"
            style="height: 550px; width: 100%; min-height: 550px"
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
      </div>
    </div>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { ClusterService } from "@/services/ClusterService"
import Loader from "@/shared/Loader.vue"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "RevenueTrends",
  components: { Loader, Widget },
  props: {
    clusterId: {
      type: String,
      required: true,
    },
    clusterRevenueAnalysis: {
      required: true,
    },
  },
  data() {
    return {
      clusterService: new ClusterService(),
      period: {},
      loading: false,
    }
  },
  computed: {
    columnChartOption() {
      if (
        !this.clusterService.trendChartData ||
        !this.clusterService.trendChartData.base ||
        !Array.isArray(this.clusterService.trendChartData.base) ||
        this.clusterService.trendChartData.base.length < 2
      ) {
        return null
      }

      const data = this.clusterService.trendChartData.base
      const headers = data[0] || []
      const dateIndex = 0
      const seriesNames = headers.slice(1)

      const dates = data.slice(1).map((row) => row[dateIndex] || "")

      const series = seriesNames.map((name, index) => {
        const seriesIndex = index + 1
        return {
          name: String(name || `Series ${index + 1}`),
          type: "bar",
          stack: "total",
          data: data.slice(1).map((row) => parseFloat(row[seriesIndex]) || 0),
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
          data: dates,
          axisLabel: {
            rotate: 45,
            interval: 0,
          },
        },
        yAxis: {
          type: "value",
        },
        series: series,
      }
    },
    lineChartOption() {
      if (
        !this.clusterService.trendChartData ||
        !this.clusterService.trendChartData.overview ||
        !Array.isArray(this.clusterService.trendChartData.overview) ||
        this.clusterService.trendChartData.overview.length < 2
      ) {
        return null
      }

      const data = this.clusterService.trendChartData.overview
      const headers = data[0] || []
      const dateIndex = 0
      const seriesNames = headers.slice(1)

      const dates = data.slice(1).map((row) => row[dateIndex] || "")

      const series = seriesNames.map((name, index) => {
        const seriesIndex = index + 1
        return {
          name: String(name || `Series ${index + 1}`),
          type: "line",
          data: data.slice(1).map((row) => parseFloat(row[seriesIndex]) || 0),
          smooth: true,
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
          boundaryGap: false,
          data: dates,
          axisLabel: {
            rotate: 45,
            interval: 0,
          },
        },
        yAxis: {
          type: "value",
        },
        series: series,
      }
    },
  },
  mounted() {
    EventBus.$on("clustersCachedDataLoading", (loading) => {
      this.loading = loading
    })
  },
  watch: {
    clusterRevenueAnalysis(newVal) {
      this.clusterService.clusterTrends = newVal
      this.clusterService.fillTrends()
    },
  },
}
</script>

<style scoped></style>

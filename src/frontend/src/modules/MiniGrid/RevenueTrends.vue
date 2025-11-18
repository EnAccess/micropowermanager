<template>
  <div>
    <widget :id="'revenue-trends'" :title="$tc('phrases.revenueTrends')">
      <div v-if="loading">
        <loader />
      </div>
      <div class="md-layout md-gutter" v-else>
        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <div style="height: 600px; width: 100%; position: relative">
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
              style="height: 600px; width: 100%; min-height: 600px"
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
        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <div style="height: 600px; width: 100%; position: relative">
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
              style="height: 600px; width: 100%; min-height: 600px"
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
    <redirection-modal
      :redirection-url="redirectionUrl"
      :imperative-item="imperativeItem"
      :dialog-active="redirectDialogActive"
    />
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import RedirectionModal from "../../shared/RedirectionModal"
import { notify } from "@/mixins/notify"
import Loader from "@/shared/Loader.vue"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "RevenueTrends",
  components: { Loader, RedirectionModal, Widget },
  mixins: [notify],
  props: {
    chartOptions: {
      required: true,
    },
    trendChartData: {
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
      redirectionUrl: "/locations/add-village",
      imperativeItem: "City",
      redirectDialogActive: false,
    }
  },
  computed: {
    columnChartOption() {
      if (
        !this.trendChartData ||
        !this.trendChartData.base ||
        !Array.isArray(this.trendChartData.base) ||
        this.trendChartData.base.length < 2
      ) {
        return null
      }

      const data = this.trendChartData.base
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
        !this.trendChartData ||
        !this.trendChartData.overview ||
        !Array.isArray(this.trendChartData.overview) ||
        this.trendChartData.overview.length < 2
      ) {
        return null
      }

      const data = this.trendChartData.overview
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
}
</script>

<style scoped></style>

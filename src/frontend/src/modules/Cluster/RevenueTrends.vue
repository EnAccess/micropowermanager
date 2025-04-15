<template>
  <widget :id="'revenue-trends'" :title="'Revenue Trends'">
    <div v-if="loading">
      <loader />
    </div>
    <div class="md-layout md-gutter" v-else>
      <div class="md-layout-item md-size-100">
        <GChart
          type="ColumnChart"
          :data="clusterService.trendChartData.base"
          :options="chartOptions"
          :resizeDebounce="500"
        />
      </div>
      <div class="md-layout-item md-size-100">
        <GChart
          type="LineChart"
          :data="clusterService.trendChartData.overview"
          :options="chartOptions"
          :resizeDebounce="500"
        />
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
      chartOptions: {
        chart: {
          legend: {
            position: "top",
          },
        },
        hAxis: {
          textPosition: "out",
          slantedText: true,
        },
        vAxis: {
          //scaleType: 'mirrorLog',
        },
        colors: ["#739e73", "#3276b1", "#78002e", "#dce775"],
        height: 550,
      },
      chartOptionsSmall: {
        chart: {
          legend: {
            position: "top",
          },
        },
        hAxis: {
          textPosition: "out",
          slantedText: true,
        },
        vAxis: {
          //scaleType: 'mirrorLog',
        },
        colors: ["#739e73", "#3276b1", "#78002e", "#dce775"],
        height: 220,
      },
    }
  },
  mounted() {
    EventBus.$on("clustersCachedDataLoading", (loading) => {
      this.loading = loading
    })
  },
  watch: {
    // eslint-disable-next-line no-unused-vars
    clusterRevenueAnalysis(newVal, oldVal) {
      this.clusterService.clusterTrends = newVal
      this.clusterService.fillTrends()
    },
  },
}
</script>

<style scoped></style>

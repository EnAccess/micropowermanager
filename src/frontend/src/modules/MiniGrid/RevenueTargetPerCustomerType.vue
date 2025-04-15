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
      <div v-else>
        <GChart
          type="ColumnChart"
          :data="targetRevenueChartData"
          :options="chartOptions"
          :resizeDebounce="500"
        ></GChart>
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
      tooltip: { isHtml: true },
      chartOptions: {
        height: 500,
        legend: "none",
        hAxis: {
          textPosition: "out",
          textStyle: {
            fontSize: 8,
          },
        },
        tooltip: { isHtml: true },
        title: this.$tc("phrases.revenueTargetsPerCustomerType"),
        vAxis: {
          viewWindow: {
            min: 0,
            max: 1,
          },
          format: "#,###%",
          title: "Percentage of Targeted Revenue %",
        },
      },
    }
  },
  methods: {},
}
</script>

<style scoped></style>

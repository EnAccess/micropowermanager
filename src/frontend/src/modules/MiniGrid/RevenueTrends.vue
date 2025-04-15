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
          <GChart
            type="ColumnChart"
            :data="trendChartData.base"
            :options="chartOptions"
            :resizeDebounce="500"
          />
        </div>
        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <GChart
            type="LineChart"
            :data="trendChartData.overview"
            :options="chartOptions"
            :resizeDebounce="500"
          />
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
}
</script>

<style scoped></style>

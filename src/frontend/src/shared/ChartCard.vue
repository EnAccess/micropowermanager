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
        <div v-else>
          <GChart
            :type="type"
            :data="chartData"
            :options="chartOptions"
            :resizeDebounce="500"
            ref="gChart"
            :events="chartEvents"
          />
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
    chartEvents: {
      select: () => {},
      click: () => {
        let parent = this
        setTimeout(function () {
          if (parent.clicks >= 2) {
            parent.chartType = parent.toggleChartType()
          }
          parent.clicks = 0
        }, 250)
      },
    },
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

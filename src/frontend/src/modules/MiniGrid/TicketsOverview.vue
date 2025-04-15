<template>
  <div>
    <widget :id="'ticketing-trends'" :title="$tc('phrases.ticketsOverview')">
      <div v-if="loading">
        <loader />
      </div>
      <div class="md-layout md-gutter" v-else>
        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <h5>{{ $tc("phrases.ticketsOverview", 2) }}</h5>
          <GChart
            type="ColumnChart"
            :data="ticketData"
            :options="chartOptions"
            :resizeDebounce="500"
          />
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
  name: "TicketsOverview",
  components: { Loader, Widget },
  props: {
    chartOptions: {
      required: true,
    },
    ticketData: {
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
    }
  },
  methods: {},
}
</script>

<style scoped></style>

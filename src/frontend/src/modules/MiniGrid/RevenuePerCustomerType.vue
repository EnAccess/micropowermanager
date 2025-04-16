<template>
  <div>
    <widget
      :id="'revenue-pie'"
      :headless="true"
      :title="$tc('phrases.revenuePerCustomerType')"
      color="red"
    >
      <div v-if="loading">
        <loader size="sm" />
      </div>
      <div v-else>
        <GChart
          type="PieChart"
          :options="donutChartOptions"
          :data="donutData"
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
  name: "RevenuePerCustomerType",
  components: { Loader, Widget },
  props: {
    donutChartOptions: {
      required: true,
    },
    donutData: {
      required: true,
    },
  },
  data() {
    return {
      loading: false,
    }
  },
  mounted() {
    EventBus.$on("miniGridCachedDataLoading", (loading) => {
      this.loading = loading
    })
  },
  methods: {},
}
</script>

<style scoped></style>

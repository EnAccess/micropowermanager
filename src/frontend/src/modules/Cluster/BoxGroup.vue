<template>
  <div class="md-layout md-gutter" v-if="Object.keys(cluster).length">
    <div
      class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style"
    >
      <box
        :box-color="'blue'"
        :center-text="true"
        :header-text="$tc('words.miniGrid')"
        :header-text-color="'#dddddd'"
        :sub-text="cluster.mini_grids.length.toString()"
        :sub-text-color="'#e3e3e3'"
        :box-icon="'map'"
      />
    </div>
    <div
      class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style"
    >
      <box
        :box-color="'orange'"
        :center-text="true"
        :header-text="$tc('words.people')"
        :sub-text="cluster.population.toString()"
        :box-icon="'supervisor_account'"
      />
    </div>
    <div
      class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style"
    >
      <box
        :box-color="'red'"
        :center-text="true"
        :header-text="$tc('phrases.connectedMeters')"
        :sub-text="cluster.meterCount.toString()"
        :box-icon="'settings_input_hdmi'"
      />
    </div>
    <div
      class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style"
    >
      <box
        v-if="cluster.revenue.toString()"
        :box-color="'green'"
        :center-text="true"
        :header-text="
          $tc('words.revenue') +
          ' (' +
          $tc('phrases.lastXDays', 1, { x: 30 }) +
          ')'
        "
        :sub-text="
          readable(cluster.revenue) +
          $store.getters['settings/getMainSettings'].currency
        "
        :box-icon="'attach_money'"
      />
    </div>
  </div>
</template>

<script>
import Box from "@/shared/Box.vue"
import { currency } from "@/mixins/currency"

export default {
  name: "BoxGroup",
  components: { Box },
  mixins: [currency],
  props: {
    cluster: {
      type: Object,
      required: true,
    },
  },
  data: () => ({
    boxData: [],
  }),
}
</script>

<style>
@media screen and (max-width: 1280px) {
  .small-size-style {
    margin-bottom: 1rem !important;
    min-height: unset;
  }
}
</style>

<template>
  <div>
    <div class="md-layout md-gutter">
      <div
        class="md-layout-item md-size-20 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :box-color="'blue'"
          :center-text="true"
          :header-text="$tc('phrases.registeredClusters')"
          :sub-text="clusters.length.toString()"
          :box-icon="'map'"
        />
      </div>
      <div
        class="md-layout-item md-size-20 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :box-color="'orange'"
          :center-text="true"
          :header-text="$tc('phrases.registeredCustomers')"
          :sub-text="readable(population).toString()"
          :box-icon="'supervisor_account'"
        />
      </div>
      <div
        class="md-layout-item md-size-20 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :box-color="'red'"
          :center-text="true"
          :header-text="$tc('phrases.connectedMeters')"
          :sub-text="readable(connections).toString()"
          :box-icon="'settings_input_hdmi'"
        />
      </div>
      <div
        class="md-layout-item md-size-20 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :box-color="'purple'"
          :center-text="true"
          :header-text="$tc('phrases.soldAppliances')"
          :sub-text="readable(soldAppliances).toString()"
          :box-icon="'shopping_cart'"
        />
      </div>
      <div
        class="md-layout-item md-size-20 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :box-color="'green'"
          :center-text="true"
          :header-text="
            $tc('words.revenue') +
            ' (' +
            $tc('phrases.lastXDays', 1, { x: 30 }) +
            ')'
          "
          :sub-text="
            readable(revenue).toString() +
            $store.getters['settings/getMainSettings'].currency
          "
          :box-icon="'attach_money'"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { currency } from "@/mixins/currency.js"
import Box from "@/shared/Box.vue"

export default {
  name: "BoxGroup",
  components: { Box },
  mixins: [currency],
  props: {
    clusters: {
      type: Array,
      required: true,
    },
  },

  computed: {
    population() {
      let population = 0
      for (let c in this.clusters) {
        population += this.clusters[c].population
      }
      return population
    },

    connections() {
      let connections = 0
      for (let c in this.clusters) {
        connections += this.clusters[c].meterCount
      }
      return connections
    },
    revenue() {
      let revenue = 0
      for (let c in this.clusters) {
        revenue += parseInt(this.clusters[c].revenue)
      }
      return revenue
    },
    soldAppliances() {
      let count = 0
      for (let c in this.clusters) {
        count += this.clusters[c].applianceCount || 0
      }
      return count
    },
  },
  methods: {
    newCluster() {
      this.$router.push("/clusters/add")
    },
  },
}
</script>

<style scoped lang="scss">
.box-group {
  display: flex;
  margin-top: 1rem;
}

.btn-log {
  background-color: #689f38 !important;

  color: white !important;
  width: 100%;
}
</style>

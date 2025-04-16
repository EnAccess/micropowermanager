<template>
  <div>
    <div class="md-layout md-gutter">
      <div
        class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :color="['#26c6da', '#00acc1']"
          :center-text="true"
          :header-text="$tc('phrases.registeredClusters')"
          :header-text-color="'#dddddd'"
          :sub-text="clusters.length.toString()"
          :sub-text-color="'#e3e3e3'"
          :box-icon="'map'"
          :box-icon-color="'#578839'"
        />
      </div>
      <div
        class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :color="['#ffa726', '#fb8c00']"
          :center-text="true"
          :header-text="$tc('phrases.registeredCustomers')"
          :header-text-color="'#dddddd'"
          :sub-text="readable(population).toString()"
          :sub-text-color="'#e3e3e3'"
          :box-icon="'supervisor_account'"
          :box-icon-color="'#385a76'"
        />
      </div>
      <div
        class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :color="['#ef5350', '#e53935']"
          :center-text="true"
          :header-text="$tc('phrases.connectedMeters')"
          :header-text-color="'#dddddd'"
          :sub-text="readable(connections).toString()"
          :sub-text-color="'#e3e3e3'"
          :box-icon="'settings_input_hdmi'"
          :box-icon-color="'#604058'"
        />
      </div>
      <div
        class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
      >
        <box
          :color="['#6eaa44', '#578839']"
          :center-text="true"
          :header-text="
            $tc('words.revenue') +
            ' (' +
            $tc('phrases.lastXDays', 1, { x: 30 }) +
            ')'
          "
          :header-text-color="'#dddddd'"
          :sub-text="
            readable(revenue).toString() +
            $store.getters['settings/getMainSettings'].currency
          "
          :sub-text-color="'#e3e3e3'"
          :box-icon="'attach_money'"
          :box-icon-color="'#5c5837'"
        />
      </div>
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
  },
  methods: {
    newCluster() {
      this.$router.push("/clusters/add")
    },
  },
}
</script>

<style>
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

<template>
  <div>
    <div class="overview-line">
      <div class="overview-line">
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
          >
            <ecreee-token-settings style="height: 100% !important" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import EcreeeTokenSettings from "./EcreeeTokenSettings.vue"

export default {
  name: "Overview",
  components: { EcreeeTokenSettings },
  data() {
    return {}
  },
  mounted() {},
  methods: {},
}
</script>

<style scoped>
.overview-line {
  margin-top: 1rem;
}
</style>

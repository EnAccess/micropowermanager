<template>
  <div>
    <div class="overview-line">
      <div class="overview-line">
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
          >
            <credential style="height: 100% !important" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Credential from "./Credential"

export default {
  name: "Overview",
  components: { Credential },
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

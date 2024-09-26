<template>
  <div>
    <div class="overview-line">
      <div class="overview-line">
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
          >
            <authentication style="height: 100% !important" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Authentication from "./Authentication"

export default {
  name: "Overview",
  components: { Authentication },
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

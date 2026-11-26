<template>
  <div class="overview-line">
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-100">
        <credential />
      </div>
    </div>
  </div>
</template>

<script>
import Credential from "./Credential.vue"

export default {
  name: "Overview",
  components: { Credential },
}
</script>

<style scoped lang="scss">
.overview-line {
  margin-top: 1rem;
}
</style>

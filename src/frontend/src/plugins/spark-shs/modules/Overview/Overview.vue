<template>
  <div>
    <widget color="primary" title="SparkShs Overview">
      <div class="md-layout md-gutter">
        <div class="md-layout-item">
          <credential></credential>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Credential from "./Credential.vue"

import Widget from "@/shared/Widget.vue"

export default {
  name: "Overview",
  components: { Widget, Credential },
  data() {
    return {}
  },
  mounted() {},
  methods: {},
}
</script>

<style scoped></style>

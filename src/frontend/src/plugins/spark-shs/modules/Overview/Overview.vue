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
import Widget from "@/shared/Widget.vue"
import Credential from "./Credential.vue"

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

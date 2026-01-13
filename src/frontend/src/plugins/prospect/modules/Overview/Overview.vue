<template>
  <div>
    <widget color="primary" title="Prospect Overview">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
        >
          <Credential />
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
    return {
      // Add any data properties needed for overview content
    }
  },
  mounted() {
    // Add any initialization logic for overview content
  },
  methods: {
    // Add any methods needed for overview functionality
  },
}
</script>

<style scoped></style>

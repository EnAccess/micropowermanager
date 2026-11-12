<template>
  <div>
    <widget color="primary" title="Calin Meter (Legacy Token API)">
      <div class="md-layout md-gutter">
        <div class="md-layout-item">
          <credential />
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
}
</script>

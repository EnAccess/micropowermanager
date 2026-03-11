<template>
  <div>
    <widget color="primary" title="SMS Transaction Parser">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <parsing-rules />
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import ParsingRules from "./ParsingRules.vue"

import Widget from "@/shared/Widget.vue"

export default {
  name: "Overview",
  components: { Widget, ParsingRules },
}
</script>

<template>
  <div id="map"></div>
</template>

<script>
import { sharedMap } from "@/mixins/mapSharing.js"
import { notify } from "@/mixins/notify.js"

export default {
  name: "SettingsMap",
  mixins: [notify, sharedMap],
}
</script>

<style scoped>
#map {
  height: 100%;
  min-height: 500px;
  width: 100%;
}

.leaflet-draw-actions a {
  background: white !important;
}
</style>

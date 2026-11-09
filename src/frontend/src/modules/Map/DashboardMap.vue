<template>
  <div class="mpm-map"></div>
</template>

<script>
import { mapComponent } from "@/mixins/mapComponent.js"
import { notify } from "@/mixins/notify.js"
import { ICONS, MARKER_TYPE, markerTooltip } from "@/services/MappingService.js"

export default {
  name: "DashboardMap",
  mixins: [mapComponent, notify],
  methods: {
    drawClusters() {
      this.drawClusterFeatures()
    },
    setMiniGridMarkers() {
      this.mappingService.markingInfos
        .filter(
          (markingInfo) => markingInfo.markerType === MARKER_TYPE.MINI_GRID,
        )
        .forEach((markingInfo) => {
          this.engine.addMarker({
            lat: markingInfo.lat,
            lon: markingInfo.lon,
            iconUrl: ICONS[markingInfo.markerType],
            tooltipHtml: markerTooltip(markingInfo),
            onClick: () =>
              this.routeToDetail("/dashboards/mini-grid", markingInfo.id),
          })
        })
    },
  },
}
</script>

<style scoped lang="scss">
.mpm-map {
  height: 100%;
  min-height: 500px;
  width: 100%;
}
</style>

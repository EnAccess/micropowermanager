<template>
  <div class="mpm-map"></div>
</template>

<script>
import { mapComponent } from "@/mixins/mapComponent.js"
import { notify } from "@/mixins/notify.js"
import { ICONS, MARKER_TYPE, markerTooltip } from "@/services/MappingService.js"

export default {
  name: "VillageMap",
  mixins: [mapComponent, notify],
  mounted() {
    if (this.marker) {
      this.onMapClick(this.placeVillageMarkerFromClick)
    }
  },
  methods: {
    drawCluster() {
      this.drawClusterFeatures({ single: true })
    },
    placeVillageMarkerFromClick({ lat, lng }) {
      const location = [lat, lng]
      this.removeExistingMarkers()
      if (!this.isWithinCluster(location)) {
        this.$emit("locationSet", {
          error:
            "Please position your village within the selected cluster boundaries.",
          geoDataItem: undefined,
        })
        return
      }
      this.placeSingleMarker(location, ICONS.VILLAGE)
      this.$emit("locationSet", {
        error: undefined,
        geoDataItem: { type: "marker", coordinates: { lat, lng } },
      })
    },
    setMiniGridMarker() {
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
            onClick: () => this.routeToDetail(markingInfo.id, markingInfo.name),
          })
        })
    },
    setVillageMarkerManually(location) {
      if (!this.isWithinCluster(location)) {
        this.$emit("locationSet", {
          error: this.$tc("phrases.positionVillageInCluster"),
          geoDataItem: undefined,
        })
        return
      }
      this.placeSingleMarker(location, ICONS.VILLAGE)
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

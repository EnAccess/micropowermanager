<template>
  <div class="mpm-map"></div>
</template>

<script>
import { mapComponent } from "@/mixins/mapComponent.js"
import { notify } from "@/mixins/notify.js"
import { ICONS, MARKER_TYPE, markerTooltip } from "@/services/MappingService.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "ClusterMap",
  mixins: [mapComponent, notify],
  props: {
    remove: {
      type: Boolean,
      default: false,
    },
  },
  mounted() {
    if (this.polygon || this.edit || this.remove) {
      this.engine.enableDraw({ polygon: this.polygon, trash: this.remove })
    }
    // this.$emit on draw.create throws for reasons tied to the draw lifecycle,
    // so the created polygon is published over the EventBus instead.
    this.engine.onDraw("create", (features) =>
      EventBus.$emit("customDrawnSet", this.featureToGeoDataItem(features[0])),
    )
    this.engine.onDraw("update", (features) =>
      this.$emit("customDrawnEdited", [this.featureToGeoDataItem(features[0])]),
    )
    this.engine.onDraw("delete", (features) =>
      this.$emit("customDrawnDeleted", features),
    )
  },
  methods: {
    // Convert a drawn/edited GeoJSON Polygon feature into the "manual" geoData
    // item the cluster forms consume. GeoJSON coordinates are [lng, lat].
    featureToGeoDataItem(feature) {
      const ring = feature.geometry.coordinates[0] || []
      const { sumLat, sumLon } = ring.reduce(
        (acc, [lng, lat]) => {
          acc.sumLat += lat
          acc.sumLon += lng
          return acc
        },
        { sumLat: 0, sumLon: 0 },
      )
      const count = ring.length || 1
      return this.mappingService.manualDrawingLocationConvert({
        type: "manual",
        geojson: { type: "Polygon", coordinates: feature.geometry.coordinates },
        display_name: "",
        selected: false,
        draw_type: "draw",
        lat: sumLat / count,
        lon: sumLon / count,
      })
    },
    drawCluster() {
      this.drawClusterFeatures({ editable: this.edit })
    },
    getDrawnPolygonGeometry() {
      return this.engine.getDrawnPolygonGeometry()
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
